<?php

namespace App\Http\Error;

final class ApiException extends \RuntimeException
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function __construct(
        public readonly ApiErrorCode $errorCode,
        public readonly int $httpStatus,
        string $message,
        public readonly ?array $details = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function badRequest(string $message = 'Bad request', ?array $details = null): self
    {
        return new self(ApiErrorCode::BAD_REQUEST, 400, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function forbidden(string $message = 'Forbidden', ?array $details = null): self
    {
        return new self(ApiErrorCode::FORBIDDEN, 403, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function notFound(string $message = 'Nout found', ?array $details = null): self
    {
        return new self(ApiErrorCode::NOT_FOUND, 404, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function conflict(string $message = 'Conflic', ?array $details = null): self
    {
        return new self(ApiErrorCode::CONFLICT, 409, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function validation(string $message = 'Validation error', ?array $details = null): self
    {
        return new self(ApiErrorCode::VALIDATION_ERROR, 422, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function rateLimited(string $message = 'Too many request', ?array $details = null): self
    {
        return new self(ApiErrorCode::RATE_LIMITED, 429, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function authRequired(string $message = 'Authentication required', ?array $details = null): self
    {
        return new self(ApiErrorCode::AUTH_REQUIRED, 401, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function authInvalid(string $message = 'Invalid authentication', ?array $details = null): self
    {
        return new self(ApiErrorCode::AUTH_INVALID, 401, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function scopeViolation(string $message = 'Scope violation', ?array $details = null): self
    {
        return new self(ApiErrorCode::SCOPE_VIOLATION, 403, $message, $details);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function internal(string $message = 'Internal error', ?array $details = null, ?\Throwable $previous = null): self
    {
        return new self(ApiErrorCode::INTERNAL_ERROR, 500, $message, $details, $previous);
    }
}

?>