<?php

/**
 * CurrencyService.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-07-28 16:29:54
 * @modified   2022-07-28 16:29:54
 */

namespace Beike\Services;

use Beike\Repositories\CurrencyRepo;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    private static $instance;

    private $currencies = [];

    public function __construct()
    {
        foreach (CurrencyRepo::all() as $result) {
            $this->currencies[$result->code] = $result;
        }
    }

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function format($amount, $currency, $value = '', $format = true)
    {
        if (empty($this->currencies)) {
            return $amount;
        }

        if (! isset($this->currencies[$currency])) {
            throw (new \Exception("Currency {$currency} not exist. Please contact the administrator to create it"));
        }

        $currencyRow = $this->currencies[$currency] ?? null;
        if (empty($currencyRow)) {
            return $amount;
        }

        $symbol_left   = $currencyRow->symbol_left;
        $symbol_right  = $currencyRow->symbol_right;
        $decimal_place = $currencyRow->decimal_place;

        if (! $value) {
            $value = $currencyRow->value;
        }

        $amount = $value ? (float) $amount * $value : (float) $amount;

        $amount = round($amount, (int) $decimal_place);

        if (! $format) {
            return $amount;
        }

        $string = '';
        if ($amount < 0) {
            $string = '-';
        }

        if ($symbol_left) {
            $string .= $symbol_left;
        }

        $string .= number_format(abs($amount), (int) $decimal_place, __('currency.decimal_point'), __('currency.thousand_point'));

        if ($symbol_right) {
            $string .= ' ' . $symbol_right;
        }

        return $string;
    }

    public function convert($value, $from, $to)
    {
        if (isset($this->currencies[$from])) {
            $from = $this->currencies[$from]->value;
        } else {
            $from = 1;
        }

        if (isset($this->currencies[$to])) {
            $to = $this->currencies[$to]->value;
        } else {
            $to = 1;
        }

        return $value * ($to / $from);
    }

    /**
     * 获取实时汇率（带缓存），供后台商品价格换算使用。
     *
     * 依次尝试：已配置的 exchangerate-api -> 免费公开接口 -> 本地货币表配置值。
     *
     * @param string|null $base 基准货币，默认取系统基础货币
     * @return array{base: string, rates: array, source: string, updated_at: string, cached: bool}
     */
    public static function getLiveRates(?string $base = null): array
    {
        $base     = $base ?: (string) system_setting('base.currency', 'USD');
        $cacheKey = 'currency:live:' . $base;

        $cached = cache()->get($cacheKey);
        if (is_array($cached) && ! empty($cached['rates'])) {
            $cached['cached'] = true;

            return $cached;
        }

        $result = [
            'base'       => $base,
            'rates'      => [],
            'source'     => 'table',
            'updated_at' => date('Y-m-d H:i:s'),
            'cached'     => false,
        ];

        // 1. 官方接口（需要配置 rate_api_key）
        try {
            $apiRates = self::getInstance()->getRatesFromApi(date('Y-m-d'));
            if ( ! empty($apiRates)) {
                $result['rates']  = $apiRates;
                $result['source'] = 'exchangerate-api';
            }
        } catch (\Throwable) {
            // 忽略，继续降级
        }

        // 2. 免费公开接口（无需 key）
        if (empty($result['rates'])) {
            try {
                $json = Http::timeout(6)->get("https://open.er-api.com/v6/latest/{$base}")->json();
                if (($json['result'] ?? '') === 'success' && ! empty($json['rates'])) {
                    $result['rates']  = $json['rates'];
                    $result['source'] = 'open.er-api.com';
                    if ( ! empty($json['time_last_update_utc'])) {
                        $result['updated_at'] = date('Y-m-d H:i:s', strtotime($json['time_last_update_utc']));
                    }
                }
            } catch (\Throwable) {
                // 忽略，继续降级
            }
        }

        // 3. 本地货币表配置值兜底
        if (empty($result['rates'])) {
            foreach (CurrencyRepo::all() as $currency) {
                $result['rates'][$currency->code] = (float) $currency->value;
            }
        }

        if ( ! empty($result['rates'])) {
            cache()->put($cacheKey, $result, 1800);
        }

        return $result;
    }

    public function getRatesFromApi(string $date)
    {
        $cacheKey = 'currency:rates:' . $date;
        if ($rates = cache()->get($cacheKey)) {
            return $rates;
        }
        if (empty(system_setting('base.rate_api_key'))) {
            return [];
        }
        $data = Http::get(
            sprintf(
                'https://v6.exchangerate-api.com/v6/%s/latest/%s',
                system_setting('base.rate_api_key'),
                system_setting('base.currency', 'USD')
            )
        )->json();
        $rates = $data['conversion_rates'] ?? [];
        cache()->set($cacheKey, $rates);

        return $rates;
    }
}
