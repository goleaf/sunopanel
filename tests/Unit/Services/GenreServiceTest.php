<?php

namespace Tests\Unit\Services;

use App\Services\Genre\GenreService;
use PHPUnit\Framework\TestCase;

class GenreServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(GenreService::class, new GenreService());
    }
}
