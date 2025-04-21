<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    
    #[Test]
    public function test_Register(): void
    {

        $this->assertTrue(true);
    }

    #[Test]
    public function test_Boot(): void
    {

        $this->assertTrue(true);
    }

}
