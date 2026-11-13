@php
    /*
     | Guard 统一报错页入口（前台）。
     |
     | 正常情况：直接调用插件渲染器输出版牌化页面（纯 PHP，无视图依赖）。
     | 插件不可用时：回退到本目录 layout.blade.php 的静态兜底页面。
     |
     | 5xx 永远不展示异常消息；4xx 仅在消息通过安全清洗后展示。
     */
    $guardStatus    = (int) ($status ?? 500);
    $guardException = $exception ?? null;
    $guardRenderer  = null;
    $guardMessage   = '';
    $guardTrace     = '';

    try {
        if (class_exists('\\Plugin\\Guard\\Services\\ErrorPageRenderer')) {
            $guardRenderer = new \Plugin\Guard\Services\ErrorPageRenderer();
            $guardMessage  = $guardRenderer->messageFromThrowable($guardException, $guardStatus);
        }
    } catch (\Throwable $e) {
        $guardRenderer = null;
    }

    try {
        if (class_exists('\\Plugin\\Guard\\Services\\TraceContext')) {
            $guardTrace = (string) \Plugin\Guard\Services\TraceContext::id();
        }
    } catch (\Throwable $e) {
        $guardTrace = '';
    }
@endphp
@if ($guardRenderer)
{!! $guardRenderer->renderHtml($guardStatus, ['variant' => 'shop', 'message' => $guardMessage, 'trace_id' => $guardTrace]) !!}
@else
@include('errors::layout', ['status' => $guardStatus])
@endif
