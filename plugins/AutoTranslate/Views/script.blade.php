@php
    $languages = collect(locales())->keyBy('code');
    $from = plugin_setting('auto_translate.source_locale', 'zh_cn') ?: 'zh_cn';
    $to = plugin_setting('auto_translate.target_locale', 'en') ?: 'en';
    $debounce = (int) plugin_setting('auto_translate.debounce_ms', 800);
    $debounce = max(200, min(5000, $debounce ?: 800));
    $assetVersion = @filemtime(base_path('plugins/AutoTranslate/Static/js/auto-translate.js')) ?: config('beike.build');
@endphp
@if ($languages->has($from) && $languages->has($to) && $from !== $to)
    <script>
        window.__AUTO_TRANSLATE_CONFIG__ = {!! json_encode([
            'endpoint' => admin_route('auto_translate.translate'),
            'from' => $from,
            'to' => $to,
            'fromName' => $languages[$from]['name'] ?? $from,
            'toName' => $languages[$to]['name'] ?? $to,
            'autoEnabled' => (bool) plugin_setting('auto_translate.auto_enabled', '1'),
            'overwrite' => (bool) plugin_setting('auto_translate.overwrite_target', '0'),
            'translateVariants' => (bool) plugin_setting('auto_translate.translate_variants', '1'),
            'debounce' => $debounce,
            'langNames' => $languages->map(fn ($item) => $item['name'])->all(),
            'texts' => [
                'panel_title' => trans('AutoTranslate::plugin.panel_title', ['from' => $languages[$from]['name'] ?? $from, 'to' => $languages[$to]['name'] ?? $to]),
                'auto_mode' => trans('AutoTranslate::plugin.auto_mode'),
                'translate_all' => trans('AutoTranslate::plugin.translate_all'),
                'translating' => trans('AutoTranslate::plugin.translating'),
                'translated' => trans('AutoTranslate::plugin.translated'),
                'translate_failed' => trans('AutoTranslate::plugin.translate_failed'),
                'skipped_existing' => trans('AutoTranslate::plugin.skipped_existing'),
                'skipped_manual' => trans('AutoTranslate::plugin.skipped_manual'),
                'empty_source' => trans('AutoTranslate::plugin.empty_source'),
                'progress' => trans('AutoTranslate::plugin.progress'),
                'done' => trans('AutoTranslate::plugin.done'),
                'translate_field' => trans('AutoTranslate::plugin.translate_field'),
                'translate_desc' => trans('AutoTranslate::plugin.translate_desc'),
                'request_failed' => trans('AutoTranslate::plugin.request_failed'),
                'no_fields' => trans('AutoTranslate::plugin.no_fields'),
                'network_error' => trans('AutoTranslate::plugin.network_error'),
                'retry' => trans('AutoTranslate::plugin.retry'),
                'manual_detected' => trans('AutoTranslate::plugin.manual_detected'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    </script>
    <script src="{{ admin_route('auto_translate.asset') }}?v={{ $assetVersion }}"></script>
@endif
