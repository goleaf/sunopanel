<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    
    #[Test]
    public function testRegister(): void
    {

        $this->assertTrue(true);
    }

    #[Test]
    public function testBoot(): void
    {

        $this->assertTrue(true);
    }

}
