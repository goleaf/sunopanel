<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Providers\AppServiceProvider;

class AppServiceProviderTest extends TestCase
{
    
    #[Test]
    public function testRegister(): void
    {
        // Arrange
        
        // Act
        
        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function testBoot(): void
    {
        // Arrange
        
        // Act
        
        // Assert
        $this->assertTrue(true);
    }

}
