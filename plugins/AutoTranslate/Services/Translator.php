<?php

namespace Plugin\AutoTranslate\Services;

use Illuminate\Support\Facades\Cache;
use Plugin\AutoTranslate\Services\Providers\GoogleProvider;
use Plugin\AutoTranslate\Services\Providers\MyMemoryProvider;

class Translator
{
    private const CACHE_DAYS = 30;

    /**
     * 翻译入口：自动识别富文本，长文本会缓存结果
     *
     * @throws \Throwable
     */
    public function translate(string $from, string $to, string $text, bool $html): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $cacheKey = $this->cacheKey($from, $to, $html ? 'html' : 'text', $text);
        $cached   = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        // 纯链接字段原样返回，避免翻译接口在 URL 中插入空格或改写路径
        if (! $html && $this->looksLikeUrl($text)) {
            return $text;
        }

        $result = $html
            ? (new HtmlTranslator($this, $from, $to))->translate($text)
            : $this->plainCached($from, $to, $text);

        Cache::put($cacheKey, $result, now()->addDays(self::CACHE_DAYS));

        return $result;
    }

    /**
     * 纯文本翻译（带缓存），供富文本逐节点翻译复用
     *
     * @throws \Throwable
     */
    public function plainCached(string $from, string $to, string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $cacheKey = $this->cacheKey($from, $to, 'plain', $text);
        $cached   = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        if ($this->looksLikeUrl($text)) {
            return $text;
        }

        $result = $this->plain($from, $to, $text);
        Cache::put($cacheKey, $result, now()->addDays(self::CACHE_DAYS));

        return $result;
    }

    /**
     * 纯文本翻译，按配置的翻译源执行；auto 模式下 Google 失败自动降级到 MyMemory
     *
     * @throws \Throwable
     */
    public function plain(string $from, string $to, string $text): string
    {
        $provider = $this->provider();

        if ($provider === 'mymemory') {
            return (new MyMemoryProvider)->translate($from, $to, $text);
        }

        if ($provider === 'google') {
            return (new GoogleProvider)->translate($from, $to, $text);
        }

        try {
            return (new GoogleProvider)->translate($from, $to, $text);
        } catch (\Throwable $e) {
            return (new MyMemoryProvider)->translate($from, $to, $text);
        }
    }

    /**
     * 整段 HTML 翻译，失败时返回 null 交给逐节点兜底方案
     */
    public function translateHtmlWhole(string $from, string $to, string $html): ?string
    {
        if ($this->provider() === 'mymemory') {
            return null;
        }

        try {
            return (new GoogleProvider)->translate($from, $to, $html);
        } catch (\Throwable $e) {
            if ($this->provider() === 'google') {
                throw $e;
            }

            return null;
        }
    }

    private function provider(): string
    {
        $provider = (string) plugin_setting('auto_translate.provider', 'auto');

        return in_array($provider, ['auto', 'google', 'mymemory'], true) ? $provider : 'auto';
    }

    private function cacheKey(string $from, string $to, string $type, string $text): string
    {
        return 'auto_translate:' . md5($this->provider() . '|' . $from . '|' . $to . '|' . $type . '|' . $text);
    }

    /**
     * 判断整段文本是否只是一个链接
     */
    private function looksLikeUrl(string $text): bool
    {
        return (bool) preg_match('#^https?://\S+$#i', $text);
    }
}
