@include('admin::errors.guard', ['status' => 500, 'exception' => $exception ?? null])
