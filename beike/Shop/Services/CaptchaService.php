<?php

namespace Beike\Shop\Services;

use Illuminate\Support\Facades\Cache;

class CaptchaService
{
    private const SESSION_KEY = 'shop_captcha';
    private const TTL         = 300;
    private const CHARACTERS  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * 生成验证码并写入缓存
     * 同一 token 重复请求时复用同一个验证码，避免页面重复加载图片导致验证码错乱
     */
    public static function generate(string $token = ''): string
    {
        $cacheKey = $token !== '' ? self::cacheKey($token) : '';

        if ($cacheKey && ($cached = Cache::get($cacheKey))) {
            return $cached;
        }

        $code   = '';
        $length = strlen(self::CHARACTERS);

        for ($i = 0; $i < 4; $i++) {
            $code .= self::CHARACTERS[random_int(0, $length - 1)];
        }

        // 并发请求同一 token 时以先写入的验证码为准
        if ($cacheKey && ! Cache::add($cacheKey, $code, self::TTL)) {
            $code = (string) Cache::get($cacheKey);
        }

        session([self::SESSION_KEY => [
            'token'      => $token,
            'code'       => strtolower($code),
            'expires_at' => now()->timestamp + self::TTL,
        ]]);

        return $code;
    }

    /**
     * 校验验证码，校验后立即失效
     */
    public static function verify($input, string $token = ''): bool
    {
        if (empty($input)) {
            return false;
        }

        $expected = null;

        if ($token !== '') {
            $expected = Cache::pull(self::cacheKey($token));
            if ($expected !== null) {
                session()->forget(self::SESSION_KEY);
            }
        }

        if ($expected === null) {
            $captcha = session(self::SESSION_KEY);
            session()->forget(self::SESSION_KEY);

            if (empty($captcha['code']) || ($captcha['expires_at'] ?? 0) < now()->timestamp) {
                return false;
            }

            $expected = $captcha['code'];
        }

        return hash_equals(strtolower((string) $expected), strtolower((string) $input));
    }

    private static function cacheKey(string $token): string
    {
        return 'shop_captcha:' . sha1($token);
    }

    /**
     * 输出验证码图片
     */
    public static function render(string $code)
    {
        if (! function_exists('imagecreatetruecolor')) {
            return self::renderSvg($code);
        }

        $width  = 132;
        $height = 44;
        $image  = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($image, 247, 248, 250);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($image, random_int(180, 220), random_int(180, 220), random_int(180, 220));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $color);
        }

        for ($i = 0; $i < 90; $i++) {
            $color = imagecolorallocate($image, random_int(160, 220), random_int(160, 220), random_int(160, 220));
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $color);
        }

        $fontFile = public_path('fonts/poppins/Poppins-SemiBold.ttf');
        $useTtf   = function_exists('imagettftext') && is_file($fontFile);

        foreach (str_split($code) as $index => $character) {
            $color = imagecolorallocate($image, random_int(20, 90), random_int(20, 90), random_int(20, 90));
            $x     = 16 + $index * 27;
            $y     = 32 + random_int(-3, 3);

            if ($useTtf) {
                imagettftext($image, 21, random_int(-16, 16), $x, $y, $color, $fontFile, $character);
            } else {
                imagestring($image, 5, $x, 14 + random_int(-3, 3), $character, $color);
            }
        }

        ob_start();
        imagepng($image);
        $content = ob_get_clean();
        imagedestroy($image);

        return response($content, 200)->withHeaders([
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * 未安装 GD 扩展时的降级输出，保证登录注册不被阻断
     */
    private static function renderSvg(string $code)
    {
        $lines = '';
        for ($i = 0; $i < 5; $i++) {
            $lines .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#%02x%02x%02x" stroke-width="1"/>',
                random_int(0, 132),
                random_int(0, 44),
                random_int(0, 132),
                random_int(0, 44),
                random_int(170, 220),
                random_int(170, 220),
                random_int(170, 220)
            );
        }

        $letters = '';
        foreach (str_split($code) as $index => $character) {
            $x      = 20 + $index * 27;
            $y      = 30 + random_int(-3, 3);
            $rotate = random_int(-16, 16);
            $grey   = random_int(34, 102);

            $letters .= sprintf(
                '<text x="%d" y="%d" transform="rotate(%d %d %d)" font-family="Verdana,sans-serif" font-size="24" font-weight="700" fill="#%02x%02x%02x">%s</text>',
                $x,
                $y,
                $rotate,
                $x,
                $y,
                $grey,
                $grey,
                $grey,
                htmlspecialchars($character, ENT_QUOTES, 'UTF-8')
            );
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="132" height="44" viewBox="0 0 132 44">'
            . '<rect width="132" height="44" rx="8" fill="#f7f8fa"/>'
            . $lines . $letters . '</svg>';

        return response($svg, 200)->withHeaders([
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }
}
