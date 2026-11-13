<?php

namespace Plugin\AutoTranslate\Services\Providers;

use Illuminate\Support\Facades\Http;

class GoogleProvider
{
    /**
     * 单次请求最大字符数，超长内容会按段落边界切分后依次翻译
     */
    private const CHUNK_SIZE = 4000;

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
        $response = Http::asForm()
            ->timeout(25)
            ->retry(2, 300)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            ])
            ->post('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl'     => $this->mapCode($from),
                'tl'     => $this->mapCode($to),
                'dt'     => 't',
                'q'      => $text,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Google HTTP ' . $response->status());
        }

        $json   = $response->json();
        $result = '';
        foreach ((array) ($json[0] ?? []) as $segment) {
            if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
                $result .= $segment[0];
            }
        }

        if ($result === '') {
            throw new \RuntimeException('Google empty response');
        }

        return $result;
    }

    /**
     * 优先在换行、段落、列表等边界处切分，尽量避免把 HTML 标签截断
     */
    private function split(string $text): array
    {
        if (mb_strlen($text) <= self::CHUNK_SIZE) {
            return [$text];
        }

        $parts  = preg_split('/(?<=\n)|(?<=<\/p>)|(?<=<\/div>)|(?<=<\/li>)|(?<=<br>)|(?<=<br\/>)/u', $text) ?: [$text];
        $chunks = [];
        $buffer = '';
        foreach ($parts as $part) {
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
            'en'    => 'en',
            'ja'    => 'ja',
            'ko'    => 'ko',
            'de'    => 'de',
            'fr'    => 'fr',
            'es'    => 'es',
            'it'    => 'it',
            'id'    => 'id',
            'ru'    => 'ru',
            'pt'    => 'pt',
            'tr'    => 'tr',
            'ar'    => 'ar',
            'vi'    => 'vi',
            'th'    => 'th',
        ];

        return $map[$code] ?? str_replace('_', '-', $code);
    }
}
