<?php
namespace Tests\Unit;

use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_book_policy_permite_crear_a_bibliotecario(): void
    {
        $policy = new BookPolicy();
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $this->assertTrue($policy->create($bibliotecario));
    }

    public function test_book_policy_no_permite_crear_a_estudiante(): void
    {
        $policy = new BookPolicy();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $this->assertFalse($policy->create($estudiante));
    }

    public function test_book_policy_permite_actualizar_y_eliminar_a_bibliotecario(): void
    {
        $policy = new BookPolicy();
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create();

        $this->assertTrue($policy->update($bibliotecario, $book));
        $this->assertTrue($policy->delete($bibliotecario, $book));
    }

    public function test_book_resource_traduce_estado_disponible(): void
    {
        $book = Book::factory()->make([
            'is_available' => true,
        ]);

        $array = (new BookResource($book))->toArray(new Request());

        $this->assertSame('Disponible', $array['is_available']);
    }

    public function test_book_resource_traduce_estado_no_disponible(): void
    {
        $book = Book::factory()->make([
            'is_available' => false,
        ]);

        $array = (new BookResource($book))->toArray(new Request());

        $this->assertSame('No Disponible', $array['is_available']);
    }
}