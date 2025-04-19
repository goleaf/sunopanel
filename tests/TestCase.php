<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Vite facade for testing
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        
        $this->mock('Illuminate\Foundation\Vite', function ($mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        });
    }
}
