<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

final class YouTubeException extends Exception
{
    public const TYPE_AUTHENTICATION = 'authentication';
    public const TYPE_UPLOAD = 'upload';
    public const TYPE_API_QUOTA = 'api_quota';
    public const TYPE_NETWORK = 'network';
    public const TYPE_FILE_NOT_FOUND = 'file_not_found';
    public const TYPE_INVALID_TOKEN = 'invalid_token';
    public const TYPE_PERMISSION_DENIED = 'permission_denied';
    public const TYPE_RATE_LIMIT = 'rate_limit';

    private string $errorType;
    private array $context;

    public function __construct(
        string $message = '',
        string $errorType = 'general',
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorType = $errorType;
        $this->context = $context;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function isRetryable(): bool
    {
        return in_array($this->errorType, [
            self::TYPE_NETWORK,
            self::TYPE_RATE_LIMIT,
            self::TYPE_API_QUOTA,
        ]);
    }

    public function getRetryDelay(): int
    {
        return match ($this->errorType) {
            self::TYPE_RATE_LIMIT => 60, // 1 minute
            self::TYPE_API_QUOTA => 3600, // 1 hour
            self::TYPE_NETWORK => 30, // 30 seconds
            default => 10, // 10 seconds
        };
    }

    public static function authentication(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_AUTHENTICATION, $context);
    }

    public static function upload(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_UPLOAD, $context);
    }

    public static function apiQuota(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_API_QUOTA, $context);
    }

    public static function network(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_NETWORK, $context);
    }

    public static function fileNotFound(string $filePath, array $context = []): self
    {
        return new self(
            "File not found: {$filePath}",
            self::TYPE_FILE_NOT_FOUND,
            array_merge($context, ['file_path' => $filePath])
        );
    }

    public static function invalidToken(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_INVALID_TOKEN, $context);
    }

    public static function permissionDenied(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_PERMISSION_DENIED, $context);
    }

    public static function rateLimit(string $message, array $context = []): self
    {
        return new self($message, self::TYPE_RATE_LIMIT, $context);
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'type' => $this->errorType,
            'context' => $this->context,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'retryable' => $this->isRetryable(),
            'retry_delay' => $this->getRetryDelay(),
        ];
    }
} 