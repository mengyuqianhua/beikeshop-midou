<?php

namespace Beike\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 商品详情图片标准化：将富文本中的本地图片统一生成为正方形裁剪资源，
 * 输出统一规格的缩略图 + 高清图 + 原图地址，供详情页淘宝式通栏展示。
 */
class ProductDetailImageService
{
    private const CACHE_DAYS = 7;

    private const DISPLAY_WIDTH = 1000;

    private const THUMB_WIDTH = 400;

    private const OUTPUT_DIR = 'cache/detail';

    /**
     * 改写商品详情 HTML 中的图片，统一为正方形规格
     */
    public static function normalize(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<img')) {
            return $html;
        }

        $cacheKey = 'product_detail_images:v2:' . md5($html);
        $cached   = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        try {
            $result = self::rewrite($html);
        } catch (\Throwable $e) {
            Log::warning('product detail image normalize failed', ['message' => $e->getMessage()]);

            return $html;
        }

        Cache::put($cacheKey, $result, now()->addDays(self::CACHE_DAYS));

        return $result;
    }

    private static function rewrite(string $html): string
    {
        $rewritten = preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) {
            $tag = $matches[0];

            if (! preg_match('/\ssrc\s*=\s*(["\'])([^"\']+)\1/i', $tag, $srcMatch)) {
                return $tag;
            }

            $src   = html_entity_decode(trim($srcMatch[2]));
            $local = self::localPath($src);

            if ($local === null) {
                return self::buildTag($tag, $src, $src, $src);
            }

            $squareUrl = self::makeWidth($local, self::DISPLAY_WIDTH);
            $thumbUrl  = self::makeWidth($local, self::THUMB_WIDTH);
            if ($squareUrl === null || $thumbUrl === null) {
                return self::buildTag($tag, $src, $src, $src);
            }

            return self::buildTag($tag, $thumbUrl, $squareUrl, image_origin($local));
        }, $html);

        return $rewritten ?? $html;
    }

    /**
     * 统一输出 1:1 容器标记，保留原图用于点击预览
     */
    private static function buildTag(string $originalTag, string $placeholder, string $src, string $origin): string
    {
        $alt = '';
        if (preg_match('/\salt\s*=\s*(["\'])(.*?)\1/is', $originalTag, $altMatch)) {
            $alt = $altMatch[2];
        }

        return sprintf(
            '<img class="pd-image lazyload" src="%s" data-src="%s" data-original="%s" width="%d" alt="%s" loading="lazy" decoding="async">',
            e($placeholder),
            e($src),
            e($origin),
            self::DISPLAY_WIDTH,
            e($alt)
        );
    }

    /**
     * 解析本地图片相对路径，外链或不存在时返回 null
     */
    private static function localPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $host    = parse_url($url, PHP_URL_HOST);
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host && $appHost) {
            $host = strtolower($host);
            if ($host !== $appHost && ! str_ends_with($host, '.' . $appHost)) {
                return null;
            }
        }

        $relative = ltrim(rawurldecode($path), '/');
        $relative = preg_replace('#/+#', '/', $relative) ?? $relative;
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        if (! is_file(public_path($relative))) {
            return null;
        }

        return $relative;
    }

    /**
     * 统一宽度、高度按原比例自适应；不做放大以避免画质损失
     */
    private static function makeWidth(string $relative, int $width): ?string
    {
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $source = public_path($relative);
        $target = self::OUTPUT_DIR . '/' . md5($relative) . '-w' . $width . '.' . $extension;

        try {
            if (! is_file(public_path($target)) || filemtime($source) > filemtime(public_path($target))) {
                ImageOrientationService::normalizeFile($source);

                ini_set('memory_limit', '-1');
                create_directories(dirname($target));

                $image = \Intervention\Image\Facades\Image::make($source);
                if ($image->width() > $width) {
                    $image->resize($width, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $image->save(public_path($target), $extension === 'png' ? 9 : 92);
            }
        } catch (\Throwable $e) {
            Log::warning('product detail image resize failed', [
                'image'   => $relative,
                'width'   => $width,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        return asset($target);
    }

    /**
     * 按后台保存的裁切参数生成正方形展示图，返回可访问 URL
     *
     * @param  array|null  $crop  ['x' => int, 'y' => int, 'width' => int, 'height' => int]
     */
    public static function cropUrl(string $image, ?array $crop = null, int $size = 1000): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $image), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $source = public_path($relative);
        if (! is_file($source)) {
            return null;
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $signature = md5($relative . '|' . json_encode($crop) . '|' . $size);
        $target    = self::OUTPUT_DIR . '/list-' . $signature . '-w' . $size . '.' . $extension;

        try {
            if (! is_file(public_path($target)) || filemtime($source) > filemtime(public_path($target))) {
                ImageOrientationService::normalizeFile($source);

                ini_set('memory_limit', '-1');
                create_directories(dirname($target));

                $canvas = \Intervention\Image\Facades\Image::make($source);
                if ($crop && ! empty($crop['width']) && ! empty($crop['height'])) {
                    $canvas->crop(
                        max(1, (int) $crop['width']),
                        max(1, (int) $crop['height']),
                        max(0, (int) ($crop['x'] ?? 0)),
                        max(0, (int) ($crop['y'] ?? 0))
                    );
                }

                if ($canvas->width() > $size) {
                    $canvas->resize($size, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $canvas->save(public_path($target), $extension === 'png' ? 9 : 92);
            }
        } catch (\Throwable $e) {
            Log::warning('product detail crop failed', [
                'image'   => $relative,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        return asset($target);
    }
}
