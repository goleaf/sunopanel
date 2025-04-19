<?php

namespace Tests\Unit\Services;

use App\Services\Batch\BatchService;
use PHPUnit\Framework\TestCase;

class BatchServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(BatchService::class, new BatchService());
    }
}
