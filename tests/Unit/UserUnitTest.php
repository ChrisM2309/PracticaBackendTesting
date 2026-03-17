<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserUnitTest extends TestCase
{
    public function test_contraseña_se_hashea(): void
    {
        $user = new User([
            'password' => 'test123',
        ]);

        $this->assertTrue($user->hasCast('password', 'hashed'));
    }


    public function test_campos_ocultos()
    {
        $user = new User([
            'name' => 'test',
            'email' => 'test@gmail.com',
            'password' => 'test123',
        ]);
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertEquals('test', $array['name']);
    }
}