@include('admin::errors.guard', ['status' => 429, 'exception' => $exception ?? null])
