<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct() {
        $this->authorizeResource(Book::class);
    }

    public function index(Request $request)
    {
        $books = Book::when($request->has('title'), function ($query) use ($request) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        })->when($request->has('isbn'), function ($query) use ($request) {
            $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
        })->when($request->has('is_available'), function ($query) use ($request) {
            $query->where('is_available', $request->boolean('is_available'));
        })
            ->paginate();

        return BookResource::collection($books);
    }

    public function show(Book $book){
        return BookResource::make($book);
    }

    // store 
    public function store(Request $request) {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'ISBN' => ['required', 'string', 'max:255', 'unique:books,ISBN'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'available_copies' => ['required', 'integer', 'min:0', 'lte:total_copies'],
        ]);

        $validated['is_available'] = $validated['available_copies'] > 0;

        $book = Book::create($validated);

        return BookResource::make($book)->response()->setStatusCode(201);
    }
    
    // update 

    public function update(Request $request, Book $book) {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'ISBN' => ['sometimes', 'required', 'string', 'max:255', 'unique:books,ISBN,'.$book->id],
            'total_copies' => ['sometimes', 'required', 'integer', 'min:1'],
            'available_copies' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        $totalCopies = $validated['total_copies'] ?? $book->total_copies;
        $availableCopies = $validated['available_copies'] ?? $book->available_copies;  

        if($availableCopies > $totalCopies) {
            return response()->json(['message' => 'Las copias disponibles no pueden pasarse de las existentes'], 422);
        }

        $validated['is_available'] = $availableCopies > 0;  

        $book->update($validated);

        return BookResource::make($book->fresh());
    }


    public function destroy(Book $book) 
    {
        if($book->loans()->whereNull('return_at')->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el libro'
            ], 422);
        }

        $book->delete();

        return response()->json(['message' => 'Libro eliminado correctamente']);
    }
}
