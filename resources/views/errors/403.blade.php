@include('errors.partials.page', [
    'code' => 403,
    'heading' => 'Access denied',
    'message' => 'Your account does not have permission to open this page or take this action.',
])
