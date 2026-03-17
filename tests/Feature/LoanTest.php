<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    // --- Historial (index) ---

    public function test_historial_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/loans');

        $response->assertStatus(401);
    }

    public function test_bibliotecario_puede_ver_historial_completo(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $book = Book::factory()->create();

        Loan::factory()->count(3)->create([
            'book_id' => $book->id,
            'user_id' => $estudiante->id,
        ]);

        $response = $this->getJson('/api/v1/loans');

        $response->assertStatus(200);

        $this->assertCount(3, $response->json('data') ?? $response->json());
    }

    public function test_estudiante_solo_ve_sus_propios_prestamos(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Sanctum::actingAs($estudiante);

        $otroEstudiante = User::factory()->create();
        $book = Book::factory()->create();

        Loan::factory()->count(2)->create(['book_id' => $book->id, 'user_id' => $estudiante->id]);
        Loan::factory()->count(3)->create(['book_id' => $book->id, 'user_id' => $otroEstudiante->id]);

        $response = $this->getJson('/api/v1/loans');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data') ?? $response->json());
    }

    public function test_docente_solo_ve_sus_propios_prestamos(): void
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        Sanctum::actingAs($docente);

        $book = Book::factory()->create();

        Loan::factory()->count(1)->create(['book_id' => $book->id, 'user_id' => $docente->id]);
        Loan::factory()->count(2)->create(['book_id' => $book->id]);

        $response = $this->getJson('/api/v1/loans');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data') ?? $response->json());
    }

    // --- Prestar (store) ---

    public function test_estudiante_puede_crear_prestamo(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Sanctum::actingAs($estudiante);

        $book = Book::factory()->create([
            'is_available' => true,
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
            'user_id' => $estudiante->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('loans', [
            'book_id' => $book->id,
            'user_id' => $estudiante->id,
            'return_at' => null,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_copies' => 1,
        ]);
    }

    public function test_docente_puede_crear_prestamo(): void
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        Sanctum::actingAs($docente);

        $book = Book::factory()->create([
            'is_available' => true,
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
            'user_id' => $docente->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('loans', [
            'book_id' => $book->id,
            'user_id' => $docente->id,
        ]);
    }

    public function test_bibliotecario_no_puede_crear_prestamo(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $book = Book::factory()->create([
            'is_available' => true,
            'available_copies' => 2,
        ]);

        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
            'user_id' => $bibliotecario->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_no_se_puede_prestar_libro_sin_copias_disponibles(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Sanctum::actingAs($estudiante);

        $book = Book::factory()->create([
            'is_available' => false,
            'total_copies' => 2,
            'available_copies' => 0,
        ]);

        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
            'user_id' => $estudiante->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Book is not available');
    }

    public function test_prestar_libro_con_ultima_copia_lo_marca_no_disponible(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Sanctum::actingAs($estudiante);

        $book = Book::factory()->create([
            'is_available' => true,
            'total_copies' => 2,
            'available_copies' => 1,
        ]);

        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
            'user_id' => $estudiante->id,
        ]);

        $response->dump(); // o dumpHeaders(), dumpSession()

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_copies' => 0,
            'is_available' => false,
        ]);
    }

    public function test_prestar_requiere_autenticacion(): void
    {
        $book = Book::factory()->create();

        $response = $this->postJson('/api/v1/loans', [
            'book_id' => $book->id,
            'user_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    // --- Devolver ---

    public function test_bibliotecario_puede_devolver_prestamo(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $book = Book::factory()->create([
            'is_available' => false,
            'total_copies' => 2,
            'available_copies' => 0,
        ]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'return_at' => null,
        ]);

        $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
        ]);

        $updatedLoan = Loan::find($loan->id);
        $this->assertNotNull($updatedLoan->return_at);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_copies' => 1,
            'is_available' => true,
        ]);
    }

    public function test_estudiante_no_puede_devolver_prestamo(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Sanctum::actingAs($estudiante);

        $book = Book::factory()->create();
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $estudiante->id,
            'return_at' => null,
        ]);

        $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(403);
    }

    public function test_docente_no_puede_devolver_prestamo(): void
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        Sanctum::actingAs($docente);

        $book = Book::factory()->create();
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $docente->id,
            'return_at' => null,
        ]);

        $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(403);
    }

    public function test_no_se_puede_devolver_prestamo_ya_devuelto(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Sanctum::actingAs($bibliotecario);

        $book = Book::factory()->create();
        $loan = Loan::factory()->returned()->create(['book_id' => $book->id]);

        $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Loan already returned');
    }

    public function test_devolucion_requiere_autenticacion(): void
    {
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id]);

        $response = $this->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(401);
    }
}