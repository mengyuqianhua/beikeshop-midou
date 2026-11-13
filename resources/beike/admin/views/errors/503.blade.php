@include('admin::errors.guard', ['status' => 503, 'exception' => $exception ?? null])
