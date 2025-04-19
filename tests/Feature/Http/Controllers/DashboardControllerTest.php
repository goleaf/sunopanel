<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    
    #[Test]
    public function testIndex(): void
    {

        $this->assertTrue(true);
    }

    #[Test]
    public function testSystemStats(): void
    {

        $this->assertTrue(true);
    }

}
