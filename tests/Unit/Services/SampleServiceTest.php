<?php

namespace Tests\Unit\Services;

use App\Services\SampleService;
use PHPUnit\Framework\TestCase;

class SampleServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SampleService::class, new SampleService());
    }
}
