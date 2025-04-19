<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use PHPUnit\Framework\TestCase;

class CacheServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CacheService::class, new CacheService());
    }
}
