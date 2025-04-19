<?php

namespace Tests\Unit\Services;

use App\Services\User\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UserService::class, new UserService());
    }
}
