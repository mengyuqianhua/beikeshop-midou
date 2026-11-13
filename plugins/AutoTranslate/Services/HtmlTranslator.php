<?php

namespace Plugin\AutoTranslate\Services;

/**
 * 富文本翻译：优先整段交给翻译接口（可完整保留标签结构）。
 * 若返回结果的标签结构与原文不一致，则退化为逐文本节点翻译，确保格式不错乱。
 */
class HtmlTranslator
{
    private const MAX_TEXT_NODES = 200;

    public function __construct(private Translator $translator, private string $from, private string $to)
    {
    }

    /**
     * @throws \Throwable
     */
    public function translate(string $html): string
    {
        $whole = $this->translator->translateHtmlWhole($this->from, $this->to, $html);
        if (is_string($whole) && $whole !== '' && $this->structureMatches($html, $whole)) {
            return $whole;
        }

        return $this->translateNodes($html);
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
