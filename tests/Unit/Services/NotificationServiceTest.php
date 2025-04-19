<?php

namespace Tests\Unit\Services;

use App\Services\NotificationService;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(NotificationService::class, new NotificationService());
    }
}
