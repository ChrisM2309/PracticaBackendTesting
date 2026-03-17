<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_lista_libros_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(401);
    }

    public function test_usuario_autenticado_puede_listar_libros(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_puede_ver_detalle_de_libro(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $book = Book::factory()->create([
            'title' => 'Historia del Real Madrid',
            'is_available' => true,
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'Historia del Real Madrid')
            ->assertJsonPath('data.is_available', 'Disponible');
    }

    public function test_bibliotecario_puede_crear_libro(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $payload = [
            'title' => 'Historia del Real Madrid',
            'description' => 'Historia del mejor club de la historia del fútbol',
            'ISBN' => '9988642245436',
            'total_copies' => 2,
            'available_copies' => 2,
        ];

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Historia del Real Madrid');

        $this->assertDatabaseHas('books', [
            'title' => 'Historia del Real Madrid',
            'ISBN' => '9988642245436',
        ]);
    }

    public function test_estudiante_no_puede_crear_libro(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Sanctum::actingAs($estudiante);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Libro restringido',
            'description' => 'No debería crear',
            'ISBN' => '1234567890123',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $response->assertStatus(403);
    }

    public function test_bibliotecario_puede_actualizar_libro(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $book = Book::factory()->create([
            'title' => 'Historia del Real Madrid',
            'total_copies' => 2,
            'available_copies' => 1,
            'is_available' => true,
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Futbol',
            'total_copies' => 4,
            'available_copies' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Futbol');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Futbol',
            'total_copies' => 4,
            'available_copies' => 2,
        ]);
    }

    public function test_bibliotecario_puede_eliminar_libro_sin_prestamos_activos(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Libro eliminado correctamente');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_no_puede_eliminar_libro_con_prestamo_activo(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $book = Book::factory()->create();
        $user = User::factory()->create();

        Loan::create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'return_at' => null,
        ]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el libro');
    }
}