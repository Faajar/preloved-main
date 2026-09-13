<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canSell();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }

    public function retry(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }
}
