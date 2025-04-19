<?php

namespace Tests\Unit\Services;

use App\Services\Track\TrackService;
use PHPUnit\Framework\TestCase;

class TrackServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TrackService::class, new TrackService());
    }
}
