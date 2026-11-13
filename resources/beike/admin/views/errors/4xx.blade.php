@php
    $guardFallbackStatus = 404;
    if (isset($exception) && is_object($exception) && method_exists($exception, 'getStatusCode')) {
        $guardFallbackStatus = (int) $exception->getStatusCode();
    }
@endphp
@include('admin::errors.guard', ['status' => $guardFallbackStatus, 'exception' => $exception ?? null])
