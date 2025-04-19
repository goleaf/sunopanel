<?php

namespace Tests\Unit\Services;

use App\Services\Logging\LoggingServiceInterface;
use App\Services\Track\TrackService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TrackServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $loggingServiceMock = Mockery::mock(LoggingServiceInterface::class);
        $loggingServiceMock->shouldReceive('logInfoMessage')->zeroOrMoreTimes();
        $loggingServiceMock->shouldReceive('logErrorMessage')->zeroOrMoreTimes();

        $this->assertInstanceOf(TrackService::class, new TrackService($loggingServiceMock));
    }
}
