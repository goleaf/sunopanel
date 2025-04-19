<?php

namespace Tests\Unit\Services;

use App\Services\Logging\LoggingService;
use PHPUnit\Framework\TestCase;

class LoggingServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(LoggingService::class, new LoggingService());
    }
}
