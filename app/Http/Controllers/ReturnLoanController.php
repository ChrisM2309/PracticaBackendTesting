<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoanResource;
use App\Models\Loan;
use Illuminate\Http\Request;

class ReturnLoanController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Loan $loan)
    {
        $this->authorize('returnLoan', $loan);

        if (! is_null($loan->return_at)) {
            return response()->json(['message' => 'Loan already returned'], 422);
        }

        $loan->update(['return_at' => now()]);
        $loan->book()->update([
            'available_copies' => $loan->book->available_copies + 1,
            'is_available' => true,
        ]);

        return response()->json(LoanResource::make($loan));
    }
}
