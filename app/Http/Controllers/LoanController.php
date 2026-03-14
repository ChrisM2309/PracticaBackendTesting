<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Loan::class, 'loan');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Loan::with('book');

        if (! $request->user()->hasRole('bibliotecario')) {
            $query->where('user_id', $request->user()->id);
        }

        $loans = $query->paginate();

        return response()->json(LoanResource::collection($loans));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLoanRequest $request)
    {
        $book = Book::find($request->input('book_id'));

        if (! $book->is_available || $book->available_copies === 0) {
            return response()->json(['message' => 'Book is not available'], 422);
        }

        $loan = DB::transaction(function () use ($book, $request) {
            $newLoan = Loan::create([
                'book_id' => $book->id,
                'user_id' => $request->user()->id,
            ]);
            
            $book->update([
                'available_copies' => $book->available_copies - 1,
                'is_available' => $book->available_copies - 1 > 0,
            ]);

            return $newLoan;
        });

        return response()->json($loan, 201);
    }    
}
