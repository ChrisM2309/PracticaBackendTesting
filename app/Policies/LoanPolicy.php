<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LoanPolicy
{
    
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['estudiante', 'docente', 'bibliotecario']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['estudiante', 'docente']);
    }

    public function returnLoan(User $user, Loan $loan): bool
    {
        return $user->hasRole('bibliotecario');
    }
}
