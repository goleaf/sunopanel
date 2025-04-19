<?php

namespace Tests\Unit\Services;

use App\Services\Playlist\PlaylistService;
use PHPUnit\Framework\TestCase;

class PlaylistServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PlaylistService::class, new PlaylistService());
    }
}
