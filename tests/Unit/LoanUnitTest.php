<?php

namespace Tests\Unit;

use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Policies\LoanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LoanUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    // LoanPolicy tests

    public function test_loan_policy_permite_ver_historial_a_bibliotecario(): void
    {
        $policy = new LoanPolicy();
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $this->assertTrue($policy->viewAny($bibliotecario));
    }

    public function test_loan_policy_permite_ver_historial_a_estudiante(): void
    {
        $policy = new LoanPolicy();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $this->assertTrue($policy->viewAny($estudiante));
    }

    public function test_loan_policy_permite_ver_historial_a_docente(): void
    {
        $policy = new LoanPolicy();
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $this->assertTrue($policy->viewAny($docente));
    }

    public function test_loan_policy_permite_crear_prestamo_a_estudiante(): void
    {
        $policy = new LoanPolicy();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $this->assertTrue($policy->create($estudiante));
    }

    public function test_loan_policy_permite_crear_prestamo_a_docente(): void
    {
        $policy = new LoanPolicy();
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $this->assertTrue($policy->create($docente));
    }

    public function test_loan_policy_no_permite_crear_prestamo_a_bibliotecario(): void
    {
        $policy = new LoanPolicy();
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $this->assertFalse($policy->create($bibliotecario));
    }

    public function test_loan_policy_permite_devolucion_solo_a_bibliotecario(): void
    {
        $policy = new LoanPolicy();
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id]);

        $this->assertTrue($policy->returnLoan($bibliotecario, $loan));
    }

    public function test_loan_policy_no_permite_devolucion_a_estudiante(): void
    {
        $policy = new LoanPolicy();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id]);

        $this->assertFalse($policy->returnLoan($estudiante, $loan));
    }

    public function test_loan_policy_no_permite_devolucion_a_docente(): void
    {
        $policy = new LoanPolicy();
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id]);

        $this->assertFalse($policy->returnLoan($docente, $loan));
    }

    // Loan model tests

    public function test_loan_is_active_cuando_return_at_es_null(): void
    {
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id, 'return_at' => null]);

        $this->assertTrue($loan->isActive);
    }

    public function test_loan_no_es_active_cuando_tiene_return_at(): void
    {
        $book = Book::factory()->create();
        $loan = Loan::factory()->returned()->create(['book_id' => $book->id]);

        $this->assertFalse($loan->isActive);
    }

    // LoanResource tests

    public function test_loan_resource_retorna_estructura_correcta(): void
    {
        $user = User::factory()->create(['name' => 'Cristiano Ronaldo']);
        $book = Book::factory()->create(['title' => 'GOAT']);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'return_at' => null,
        ]);
        $loan->load('book');

        $array = (new LoanResource($loan))->toArray(new Request());

        $this->assertSame($loan->id, $array['id']);
        $this->assertSame('Cristiano Ronaldo', $array['requester_name']);
        $this->assertTrue($array['is_active']);
        $this->assertNull($array['return_at']);
    }

    public function test_loan_resource_is_active_false_cuando_devuelto(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $loan = Loan::factory()->returned()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
        $loan->load('book');

        $array = (new LoanResource($loan))->toArray(new Request());

        $this->assertFalse($array['is_active']);
        $this->assertNotNull($array['return_at']);
    }
}