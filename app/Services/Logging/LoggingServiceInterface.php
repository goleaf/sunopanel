<?php

declare(strict_types=1);

namespace App\Services\Logging;

interface LoggingServiceInterface
{
    public function logInfoMessage(string $message, array $context = []): void;

    public function logErrorMessage(string $message, array $context = []): void;

    // Add other methods from LoggingService if they are needed by clients
} 