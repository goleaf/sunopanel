<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UserStoreRequest;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStoreRequestTest extends TestCase
{
    private UserStoreRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UserStoreRequest();
    }

    #[Test]
    public function testAuthorize(): void
    {
        $user = new \stdClass();
        $user->role = 'admin';
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        $this->assertTrue($this->request->authorize());
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
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('max:255', $rules['email']);
        $this->assertContains('unique:users', $rules['email']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('confirmed', $rules['password']);
        $hasPasswordRule = false;
        foreach ($rules['password'] as $rule) {
            if ($rule instanceof Password) {
                $hasPasswordRule = true;
                break;
            }
        }
        $this->assertTrue($hasPasswordRule, 'The password rule is missing');
        $this->assertContains('sometimes', $rules['role']);
        $this->assertContains('string', $rules['role']);
        $this->assertContains('in:admin,user', $rules['role']);
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
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('email.email', $messages);
        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertArrayHasKey('password.required', $messages);
        $this->assertArrayHasKey('password.confirmed', $messages);
        $this->assertArrayHasKey('role.in', $messages);
        $this->assertArrayHasKey('avatar.image', $messages);
        $this->assertArrayHasKey('avatar.mimes', $messages);
        $this->assertArrayHasKey('avatar.max', $messages);
        
        $this->assertEquals('The name field is required.', $messages['name.required']);
        $this->assertEquals('The email field is required.', $messages['email.required']);
        $this->assertEquals('Please enter a valid email address.', $messages['email.email']);
        $this->assertEquals('This email address is already in use.', $messages['email.unique']);
        $this->assertEquals('The password field is required.', $messages['password.required']);
        $this->assertEquals('The password confirmation does not match.', $messages['password.confirmed']);
        $this->assertEquals('The selected role is invalid.', $messages['role.in']);
        $this->assertEquals('The file must be an image.', $messages['avatar.image']);
        $this->assertEquals('The image must be a file of type: jpeg, png, jpg, gif.', $messages['avatar.mimes']);
        $this->assertEquals('The image may not be greater than 2MB.', $messages['avatar.max']);
    }
}
