<?php

namespace App\Http\Error;

enum ApiErrorCode: string
{
    case AUTH_REQUIRED = 'AUTH_REQUIRED';
    case AUTH_INVALID = 'AUTH_INVALID';
    case BAD_REQUEST = 'BAD_REQUEST';
    case FORBIDDEN = 'FORBIDDEN';
    case SCOPE_VIOLATION = 'SCOPE_VIOLATION';
    case NOT_FOUND = 'NOT_FOUND';
    case CONFLICT = 'CONFLICT';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case RATE_LIMITED = 'RATE_LIMITED';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
}

?>