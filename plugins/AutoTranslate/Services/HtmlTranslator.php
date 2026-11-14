<?php

namespace Plugin\AutoTranslate\Services;

/**
 * 富文本翻译：优先整段交给翻译接口（可完整保留标签结构）。
 * 若返回结果的标签结构与原文不一致，则退化为逐文本节点翻译，确保格式不错乱。
 */
class HtmlTranslator
{
    private const MAX_TEXT_NODES = 200;

    /**
     * 链接类属性，翻译前用占位符替换，避免翻译接口在长 URL 中插入空格导致图片/链接失效
     */
    private const URL_ATTRIBUTES = 'src|href|data-src|data-original|data-bg|poster|srcset|action';

    private const URL_TOKEN_PREFIX = 'ATXURL';

    private const URL_TOKEN_SUFFIX = 'XAT';

    public function __construct(private Translator $translator, private string $from, private string $to)
    {
    }

    /**
     * @throws \Throwable
     */
    public function translate(string $html): string
    {
        [$prepared, $urls] = $this->protectUrls($html);

        $whole = $this->translator->translateHtmlWhole($this->from, $this->to, $prepared);
        if (is_string($whole) && $whole !== '' && $this->structureMatches($prepared, $whole)) {
            $restored = $this->restoreUrls($whole, $urls);
            if (! preg_match('/' . self::URL_TOKEN_PREFIX . '\d+' . self::URL_TOKEN_SUFFIX . '/i', $restored)) {
                return $restored;
            }
        }

        return $this->translateNodes($html);
    }

    /**
     * 将属性中的 URL 替换为占位符
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function protectUrls(string $html): array
    {
        $urls     = [];
        $prepared = preg_replace_callback(
            '/(\s(?:' . self::URL_ATTRIBUTES . ')\s*=\s*)(["\'])([^"\']*)\2/iu',
            function (array $matches) use (&$urls) {
                $value = $matches[3];
                if (trim($value) === '' || str_starts_with($value, 'data:')) {
                    return $matches[0];
                }

                $token         = self::URL_TOKEN_PREFIX . count($urls) . self::URL_TOKEN_SUFFIX;
                $urls[$token]  = $value;

                return $matches[1] . '"' . $token . '"';
            },
            $html
        );

        return [$prepared ?? $html, $urls];
    }

    /**
     * 翻译完成后还原占位符
     */
    private function restoreUrls(string $html, array $urls): string
    {
        if (! $urls) {
            return $html;
        }

        return preg_replace_callback(
            '/' . self::URL_TOKEN_PREFIX . '(\d+)' . self::URL_TOKEN_SUFFIX . '/i',
            function (array $matches) use ($urls) {
                $token = self::URL_TOKEN_PREFIX . $matches[1] . self::URL_TOKEN_SUFFIX;

                return $urls[$token] ?? $matches[0];
            },
            $html
        ) ?? $html;
    }

    /**
     * 对比标签序列，判断整段翻译是否破坏了 HTML 结构
     */
    private function structureMatches(string $source, string $translated): bool
    {
        return $this->tags($source) === $this->tags($translated);
    }

    private function tags(string $html): array
    {
        preg_match_all('/<\/?([a-z0-9]+)/i', $html, $matches);

        return array_map('strtolower', $matches[1] ?? []);
    }

    /**
     * 逐文本节点翻译，标签、属性、图片、链接全部保持原样
     *
     * @throws \Throwable
     */
    private function translateNodes(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="auto-translate-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($document);
        $root  = $xpath->query('//*[@id="auto-translate-root"]')->item(0);
        if (! $root) {
            throw new \RuntimeException(trans('AutoTranslate::plugin.rich_text_parse_failed'));
        }

        $nodes = $xpath->query('.//text()', $root);
        if ($nodes === false) {
            return $html;
        }

        if ($nodes->length > self::MAX_TEXT_NODES) {
            throw new \RuntimeException(trans('AutoTranslate::plugin.rich_text_too_complex'));
        }

        foreach ($nodes as $node) {
            $parentName = strtolower($node->parentNode->nodeName ?? '');
            if (in_array($parentName, ['script', 'style'], true)) {
                continue;
            }

            $text = $node->nodeValue;
            if ($text === null || trim($text) === '') {
                continue;
            }

            $translated = $this->translator->plainCached($this->from, $this->to, $text);
            if ($translated !== '') {
                $node->nodeValue = $translated;
            }
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }
}
