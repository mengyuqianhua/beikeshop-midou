<?php

namespace Plugin\AutoTranslate\Services\Providers;

use Illuminate\Support\Facades\Http;

/**
 * 免费备用翻译源，仅在 Google 接口不可用时使用。
 * 该接口限制单次文本长度，因此按字符数切分后逐段翻译。
 */
class MyMemoryProvider
{
    private const CHUNK_SIZE = 450;

    public function translate(string $from, string $to, string $text): string
    {
        $result = '';
        foreach ($this->split($text) as $chunk) {
            $result .= $this->request($from, $to, $chunk);
        }

        return $result;
    }

    private function request(string $from, string $to, string $text): string
    {
        $response = Http::timeout(25)
            ->retry(1, 300)
            ->get('https://api.mymemory.translated.net/get', [
                'q'        => $text,
                'langpair' => $this->mapCode($from) . '|' . $this->mapCode($to),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('MyMemory HTTP ' . $response->status());
        }

        $json   = $response->json();
        $status = (int) ($json['responseStatus'] ?? 0);
        $result = (string) ($json['responseData']['translatedText'] ?? '');

        if ($status !== 200 || $result === '') {
            $message = (string) ($json['responseDetails'] ?? 'MyMemory response error');

            throw new \RuntimeException($message);
        }

        return $result;
    }

    private function split(string $text): array
    {
        if (mb_strlen($text) <= self::CHUNK_SIZE) {
            return [$text];
        }

        $chunks = [];
        $buffer = '';
        foreach (preg_split('/(?<=\n)/u', $text) ?: [$text] as $part) {
            if ($buffer !== '' && mb_strlen($buffer . $part) > self::CHUNK_SIZE) {
                $chunks[] = $buffer;
                $buffer   = '';
            }
            $buffer .= $part;

            while (mb_strlen($buffer) > self::CHUNK_SIZE) {
                $chunks[] = mb_substr($buffer, 0, self::CHUNK_SIZE);
                $buffer   = mb_substr($buffer, self::CHUNK_SIZE);
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }

    private function mapCode(string $code): string
    {
        $map = [
            'zh_cn' => 'zh-CN',
            'zh_hk' => 'zh-TW',
        ];

        return $map[$code] ?? $code;
    }
}
