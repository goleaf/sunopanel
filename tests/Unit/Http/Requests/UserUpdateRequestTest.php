<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UserUpdateRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class UserUpdateRequestTest extends TestCase
{
    private UserUpdateRequest $request;
    private \stdClass $userObj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UserUpdateRequest();
        
        // Setup user object
        $this->userObj = new \stdClass();
        $this->userObj->id = 1;
        
        // Mock the route resolver
        $this->request->setRouteResolver(function () {
            $route = $this->createMock(Route::class);
            $route->method('parameter')
                ->with('user')
                ->willReturn($this->userObj);
            
            return $route;
        });
    }

    #[Test]
    public function testAuthorize(): void
    {
        // Test as admin user
        $adminUser = new \stdClass();
        $adminUser->role = 'admin';
        $adminUser->id = 2;
        
        $this->request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        
        $this->assertTrue($this->request->authorize(), 'Admin user should be authorized');
        
        // Test as the same user
        $sameUser = new \stdClass();
        $sameUser->role = 'user';
        $sameUser->id = 1; // Same ID as in route parameter
        
        $this->request->setUserResolver(function () use ($sameUser) {
            return $sameUser;
        });
        
        $this->assertTrue($this->request->authorize(), 'Same user should be authorized');
        
        // Test as different non-admin user
        $differentUser = new \stdClass();
        $differentUser->role = 'user';
        $differentUser->id = 3; // Different ID than route parameter
        
        $this->request->setUserResolver(function () use ($differentUser) {
            return $differentUser;
        });
        
        $this->assertFalse($this->request->authorize(), 'Different non-admin user should not be authorized');
    }

    #[Test]
    public function testRules(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('role', $rules);
        $this->assertArrayHasKey('avatar', $rules);
        
        // Check name validation rules
        $this->assertContains('sometimes', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        
        // Check email validation rules
        $this->assertContains('sometimes', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('max:255', $rules['email']);
        
        // Check for a rule in the email rules (not using instanceof as it might be various rule types)
        $hasComplexRule = false;
        foreach ($rules['email'] as $rule) {
            if (is_object($rule)) {
                $hasComplexRule = true;
                break;
            }
        }
        $this->assertTrue($hasComplexRule, 'A complex rule object is missing from email rules');
        
        // Check password validation rules
        $this->assertContains('sometimes', $rules['password']);
        $this->assertContains('nullable', $rules['password']);
        $this->assertContains('confirmed', $rules['password']);
        
        // Check there's a Password instance in the password rules
        $hasPasswordRule = false;
        foreach ($rules['password'] as $rule) {
            if ($rule instanceof Password) {
                $hasPasswordRule = true;
                break;
            }
        }
        $this->assertTrue($hasPasswordRule, 'The password rule is missing');
        
        // Check role validation rules
        $this->assertContains('sometimes', $rules['role']);
        $this->assertContains('string', $rules['role']);
        $this->assertContains('in:admin,user', $rules['role']);
        
        // Check avatar validation rules
        $this->assertContains('sometimes', $rules['avatar']);
        $this->assertContains('image', $rules['avatar']);
        $this->assertContains('mimes:jpeg,png,jpg,gif', $rules['avatar']);
        $this->assertContains('max:2048', $rules['avatar']);
    }

    #[Test]
    public function testMessages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.string', $messages);
        $this->assertArrayHasKey('name.max', $messages);
        $this->assertArrayHasKey('email.email', $messages);
        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertArrayHasKey('password.confirmed', $messages);
        $this->assertArrayHasKey('role.in', $messages);
        $this->assertArrayHasKey('avatar.image', $messages);
        $this->assertArrayHasKey('avatar.mimes', $messages);
        $this->assertArrayHasKey('avatar.max', $messages);
        
        $this->assertEquals('The name must be a string.', $messages['name.string']);
        $this->assertEquals('The name may not be greater than 255 characters.', $messages['name.max']);
        $this->assertEquals('Please enter a valid email address.', $messages['email.email']);
        $this->assertEquals('This email address is already in use.', $messages['email.unique']);
        $this->assertEquals('The password confirmation does not match.', $messages['password.confirmed']);
        $this->assertEquals('The selected role is invalid.', $messages['role.in']);
        $this->assertEquals('The file must be an image.', $messages['avatar.image']);
        $this->assertEquals('The image must be a file of type: jpeg, png, jpg, gif.', $messages['avatar.mimes']);
        $this->assertEquals('The image may not be greater than 2MB.', $messages['avatar.max']);
    }
}
