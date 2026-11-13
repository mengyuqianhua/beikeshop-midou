<?php

namespace Beike\Services;

/**
 * 按 EXIF Orientation 对图片做物理旋转校正。
 *
 * 手机拍摄的照片通常只写入 EXIF 方向标记而不旋转像素。浏览器展示原图时会
 * 自动按标记旋转，但服务端生成缩略图（Intervention/GD）时会丢弃 EXIF，
 * 导致列表页、详情页与原图方向不一致。这里在存储时把像素真正旋转到位，
 * 并清除方向标记，使后续所有展示场景方向一致。
 *
 * 支持 JPEG(APP1 Exif)、PNG(eXIf)、WebP(EXIF chunk)。
 */
class ImageOrientationService
{
    private const ORIENTATION_TAG = 0x0112;

    /**
     * 若图片带有非 1 的 EXIF 方向标记，则旋转像素并原地重写文件。
     *
     * @return bool 是否执行了校正
     */
    public static function normalizeFile(string $path): bool
    {
        try {
            if (! is_file($path) || ! is_writable($path)) {
                return false;
            }

            $orientation = self::readOrientation($path);
            if ($orientation <= 1 || $orientation > 8) {
                return false;
            }

            $info = @getimagesize($path);
            if (! $info) {
                return false;
            }

            $type  = $info[2];
            $image = self::loadImage($path, $type);
            if (! $image) {
                return false;
            }

            $rotated = self::rotate($image, $orientation);
            if (! $rotated) {
                imagedestroy($image);

                return false;
            }
            if ($rotated !== $image) {
                imagedestroy($image);
            }

            $tmp = $path . '.orientation-' . bin2hex(random_bytes(4)) . '.tmp';
            if (! self::saveImage($rotated, $tmp, $type)) {
                imagedestroy($rotated);
                @unlink($tmp);

                return false;
            }
            imagedestroy($rotated);

            @chmod($tmp, fileperms($path) & 0777);
            if (! @rename($tmp, $path)) {
                @unlink($tmp);

                return false;
            }

            clearstatcache(true, $path);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 读取图片 EXIF 方向标记，无法识别时返回 1。
     */
    public static function readOrientation(string $path): int
    {
        if (! is_file($path)) {
            return 1;
        }

        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return 1;
        }

        try {
            $signature = fread($handle, 12);
            if (strlen($signature) < 12) {
                return 1;
            }

            if (str_starts_with($signature, "\xFF\xD8\xFF")) {
                $tiff = self::jpegExifBlock($handle);
            } elseif (str_starts_with($signature, "\x89PNG\r\n\x1A\n")) {
                $tiff = self::pngExifBlock($handle);
            } elseif (str_starts_with($signature, 'RIFF') && substr($signature, 8, 4) === 'WEBP') {
                $tiff = self::webpExifBlock($handle);
            } else {
                return 1;
            }
        } finally {
            fclose($handle);
        }

        return $tiff ? self::parseTiffOrientation($tiff) : 1;
    }

    /**
     * 是否是可以按 EXIF 方向校正的图片格式。
     */
    public static function isSupportedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    /**
     * 在 JPEG 分段中查找 APP1 Exif 数据段。
     */
    private static function jpegExifBlock($handle): ?string
    {
        fseek($handle, 2);
        $read  = 2;
        $limit = 1024 * 1024;

        while ($read < $limit) {
            $marker = fread($handle, 2);
            $read += 2;
            if (strlen($marker) < 2 || $marker[0] !== "\xFF") {
                return null;
            }

            $code = ord($marker[1]);
            // 无长度的独立标记
            if ($code === 0x01 || $code === 0xD8 || ($code >= 0xD0 && $code <= 0xD7)) {
                continue;
            }
            // 到达图像数据或文件结尾，Exif 只会出现在之前
            if ($code === 0xD9 || $code === 0xDA) {
                return null;
            }

            $lengthBytes = fread($handle, 2);
            $read += 2;
            if (strlen($lengthBytes) < 2) {
                return null;
            }

            $length     = unpack('n', $lengthBytes)[1];
            $payloadLen = $length - 2;
            if ($payloadLen < 0) {
                return null;
            }

            if ($code === 0xE1 && $payloadLen > 6) {
                $payload = fread($handle, $payloadLen);
                $read += strlen($payload);

                if (str_starts_with($payload, "Exif\0\0")) {
                    return substr($payload, 6);
                }

                continue;
            }

            if ($payloadLen > 0) {
                fseek($handle, $payloadLen, SEEK_CUR);
                $read += $payloadLen;
            }
        }

        return null;
    }

    /**
     * 在 PNG 块中查找 eXIf 块。
     */
    private static function pngExifBlock($handle): ?string
    {
        fseek($handle, 8);
        $read  = 8;
        $limit = 8 * 1024 * 1024;

        while ($read < $limit) {
            $header = fread($handle, 8);
            $read += 8;
            if (strlen($header) < 8) {
                return null;
            }

            $length = unpack('N', substr($header, 0, 4))[1];
            $type   = substr($header, 4, 4);

            // 图像数据之后不会再有 Exif
            if ($type === 'IDAT' || $type === 'IEND' || $length < 0 || $length > 16 * 1024 * 1024) {
                return null;
            }

            if ($type === 'eXIf') {
                return fread($handle, $length);
            }

            $skip = $length + 4; // 数据 + CRC
            fseek($handle, $skip, SEEK_CUR);
            $read += $skip;
        }

        return null;
    }

    /**
     * 在 WebP RIFF 块中查找 EXIF 块。
     */
    private static function webpExifBlock($handle): ?string
    {
        fseek($handle, 12);
        $read  = 12;
        $limit = 8 * 1024 * 1024;

        while ($read < $limit) {
            $header = fread($handle, 8);
            $read += 8;
            if (strlen($header) < 8) {
                return null;
            }

            $type   = substr($header, 0, 4);
            $length = unpack('V', substr($header, 4, 4))[1];

            if ($type === 'EXIF') {
                $payload = fread($handle, $length);
                if (str_starts_with($payload, "Exif\0\0")) {
                    $payload = substr($payload, 6);
                }

                return $payload;
            }

            $skip = $length + ($length % 2); // RIFF 块按偶数字节对齐
            fseek($handle, $skip, SEEK_CUR);
            $read += $skip;
        }

        return null;
    }

    /**
     * 解析 TIFF 头中 IFD0 的 Orientation 标记。
     */
    private static function parseTiffOrientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        $byteOrder = substr($tiff, 0, 2);
        if ($byteOrder === 'II') {
            $short = 'v';
            $long  = 'V';
        } elseif ($byteOrder === 'MM') {
            $short = 'n';
            $long  = 'N';
        } else {
            return 1;
        }

        if (unpack($short, substr($tiff, 2, 2))[1] !== 42) {
            return 1;
        }

        $ifdOffset = unpack($long, substr($tiff, 4, 4))[1];
        if ($ifdOffset < 8 || $ifdOffset + 2 > strlen($tiff)) {
            return 1;
        }

        $count  = unpack($short, substr($tiff, $ifdOffset, 2))[1];
        $offset = $ifdOffset + 2;

        for ($i = 0; $i < $count; $i++) {
            if ($offset + 12 > strlen($tiff)) {
                break;
            }

            $entry = substr($tiff, $offset, 12);
            $tag   = unpack($short, substr($entry, 0, 2))[1];

            if ($tag === self::ORIENTATION_TAG) {
                // SHORT 类型(3)的值存放在 12 字节条目的前 2 个值字节中
                $value = unpack($short, substr($entry, 8, 2))[1];

                return $value >= 1 && $value <= 8 ? $value : 1;
            }

            $offset += 12;
        }

        return 1;
    }

    /**
     * @param mixed $type IMAGETYPE_* 常量
     * @return mixed GD 资源，失败返回 null
     */
    private static function loadImage(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default        => null,
        };
    }

    /**
     * 按 EXIF 方向旋转/镜像像素。
     *
     * @param mixed $image
     * @return mixed 旋转后的 GD 资源，失败返回 null
     */
    private static function rotate($image, int $orientation)
    {
        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);

                return $image;

            case 3:
                return self::rotateImage($image, 180);

            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);

                return $image;

            case 5:
                $rotated = self::rotateImage($image, 270);
                if ($rotated) {
                    imageflip($rotated, IMG_FLIP_HORIZONTAL);
                }

                return $rotated;

            case 6:
                return self::rotateImage($image, 270);

            case 7:
                $rotated = self::rotateImage($image, 90);
                if ($rotated) {
                    imageflip($rotated, IMG_FLIP_HORIZONTAL);
                }

                return $rotated;

            case 8:
                return self::rotateImage($image, 90);
        }

        return null;
    }

    /**
     * @param mixed $image
     * @return mixed
     */
    private static function rotateImage($image, int $angle)
    {
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $rotated = @imagerotate($image, $angle, 0);
        if ($rotated === false) {
            return null;
        }

        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        return $rotated;
    }

    /**
     * 按原格式写回临时文件。
     *
     * @param mixed $image
     */
    private static function saveImage($image, string $target, int $type): bool
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return @imagejpeg($image, $target, 95);

            case IMAGETYPE_PNG:
                imagealphablending($image, false);
                imagesavealpha($image, true);

                return @imagepng($image, $target, 6);

            case IMAGETYPE_WEBP:
                if (! function_exists('imagewebp')) {
                    return false;
                }
                imagealphablending($image, false);
                imagesavealpha($image, true);

                return @imagewebp($image, $target, 95);
        }

        return false;
    }
}
