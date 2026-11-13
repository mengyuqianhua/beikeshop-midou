<?php

namespace Plugin\AutoTranslate\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugin\AutoTranslate\Services\Translator;

class TranslateController
{
    private const MAX_TEXT_LENGTH = 50000;

    public function translate(Request $request): JsonResponse
    {
        $from = (string) $request->post('from', plugin_setting('auto_translate.source_locale', 'zh_cn'));
        $to   = (string) $request->post('to', plugin_setting('auto_translate.target_locale', 'en'));
        $text = (string) $request->post('text', '');
        $html = filter_var($request->post('html', false), FILTER_VALIDATE_BOOLEAN);

        $codes = collect(locales())->pluck('code')->all();
        if (! in_array($from, $codes, true) || ! in_array($to, $codes, true)) {
            return json_fail(trans('AutoTranslate::plugin.invalid_locale'));
        }

        if ($from === $to) {
            return json_fail(trans('AutoTranslate::plugin.invalid_locale'));
        }

        if (trim($text) === '') {
            return json_fail(trans('AutoTranslate::plugin.empty_text'));
        }

        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            return json_fail(trans('AutoTranslate::plugin.text_too_long'));
        }

        try {
            $result = (new Translator)->translate($from, $to, $text, $html);

            return json_success(trans('AutoTranslate::plugin.translated'), [
                'result' => $result,
                'from'   => $from,
                'to'     => $to,
            ]);
        } catch (\Throwable $e) {
            Log::warning('auto_translate failed', [
                'from'    => $from,
                'to'      => $to,
                'html'    => $html,
                'length'  => mb_strlen($text),
                'message' => $e->getMessage(),
            ]);

            return json_fail(trans('AutoTranslate::plugin.translate_failed') . ': ' . $this->shortMessage($e));
        }
    }

    /**
     * 输出插件前端脚本，避免依赖静态资源发布流程
     */
    public function asset()
    {
        $path = base_path('plugins/AutoTranslate/Static/js/auto-translate.js');
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type'  => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function shortMessage(\Throwable $e): string
    {
        $message = trim($e->getMessage());

        return mb_strlen($message) > 120 ? mb_substr($message, 0, 120) . '...' : $message;
    }
}
