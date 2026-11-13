@php
    /*
     | Guard 统一报错页入口（后台）。
     |
     | 与前台共用同一渲染器，仅切换 variant=admin（后台首页 / 重新登录 / 后台皮肤）。
     | 插件不可用时回退到 admin::errors.layout 静态兜底页。
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
{!! $guardRenderer->renderHtml($guardStatus, ['variant' => 'admin', 'message' => $guardMessage, 'trace_id' => $guardTrace]) !!}
@else
@include('admin::errors.layout', ['status' => $guardStatus])
@endif
