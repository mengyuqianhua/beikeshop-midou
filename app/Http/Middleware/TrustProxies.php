<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * 站点通过 Cloudflare 接入，源站收到的 REMOTE_ADDR 是 Cloudflare 边缘节点 IP，
     * 且每次请求可能在多个节点间变化。若不信任这些代理，
     * $request->ip() 会变成边缘节点 IP，导致按 IP 限流、日志记录全部失真。
     * 仅信任 Cloudflare 官方网段，直连源站时仍使用真实 REMOTE_ADDR。
     *
     * @var array|string|null
     */
    protected $proxies = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR   |
        Request::HEADER_X_FORWARDED_HOST  |
        Request::HEADER_X_FORWARDED_PORT  |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
