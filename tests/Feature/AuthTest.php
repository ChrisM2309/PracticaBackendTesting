<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_it_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('test123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'test123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user',
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_rol()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');

        $this->assertTrue($user->hasRole('bibliotecario'));
    }

    public function test_user_profile()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response =  $this->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ]
            ]);
    }

    public function test_logout()
    {
        $user = User::factory()->create();
        $user->createToken('test-token');
        Sanctum::actingAs($user);

        $response =  $this->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);

        $this->assertCount(0, $user->refresh()->tokens);
    }

    public function test_wrong_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('test123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'test122',
        ]);


        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_sin_usuario_perfil()
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }


    public function test_estudiante_rutas_admin()
    {
        $book = Book::factory()->create();

        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $loan = Loan::factory()->create(
            [
                'book_id' => $book->id,
                'user_id' => $estudiante->id
            ]
        );

        Sanctum::actingAs($estudiante);

        $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(403);
    }
}