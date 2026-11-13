<?php

return [
    [
        'name'            => 'auto_enabled',
        'label_key'       => 'plugin.auto_enabled',
        'description_key' => 'plugin.auto_enabled_desc',
        'type'            => 'bool',
        'required'        => false,
    ],
    [
        'name'            => 'source_locale',
        'label_key'       => 'plugin.source_locale',
        'description_key' => 'plugin.source_locale_desc',
        'type'            => 'select',
        'required'        => true,
        'options'         => collect(locales())->map(fn ($locale) => [
            'value' => $locale['code'],
            'label' => $locale['name'],
        ])->values()->all(),
    ],
    [
        'name'            => 'target_locale',
        'label_key'       => 'plugin.target_locale',
        'description_key' => 'plugin.target_locale_desc',
        'type'            => 'select',
        'required'        => true,
        'options'         => collect(locales())->map(fn ($locale) => [
            'value' => $locale['code'],
            'label' => $locale['name'],
        ])->values()->all(),
    ],
    [
        'name'            => 'provider',
        'label_key'       => 'plugin.provider',
        'description_key' => 'plugin.provider_desc',
        'type'            => 'select',
        'required'        => true,
        'options'         => [
            ['value' => 'auto', 'label_key' => 'plugin.provider_auto'],
            ['value' => 'google', 'label_key' => 'plugin.provider_google'],
            ['value' => 'mymemory', 'label_key' => 'plugin.provider_mymemory'],
        ],
    ],
    [
        'name'            => 'debounce_ms',
        'label_key'       => 'plugin.debounce_ms',
        'description_key' => 'plugin.debounce_ms_desc',
        'type'            => 'string',
        'required'        => false,
        'rules'           => 'nullable|integer|min:200|max:5000',
    ],
    [
        'name'            => 'overwrite_target',
        'label_key'       => 'plugin.overwrite_target',
        'description_key' => 'plugin.overwrite_target_desc',
        'type'            => 'bool',
        'required'        => false,
    ],
    [
        'name'            => 'translate_variants',
        'label_key'       => 'plugin.translate_variants',
        'description_key' => 'plugin.translate_variants_desc',
        'type'            => 'bool',
        'required'        => false,
    ],
];
