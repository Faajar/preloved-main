<?php

namespace App\Payments\Contracts;

use App\Models\PaymentAttempt;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\PaymentStatusResult;
use App\Payments\Data\PreparedCheckoutRequest;
use App\Payments\Data\VerifiedPaymentEvent;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function prepareCheckout(PaymentAttempt $attempt): PreparedCheckoutRequest;

    public function createCheckout(PaymentAttempt $attempt, PreparedCheckoutRequest $request): CheckoutSession;

    public function checkStatus(PaymentAttempt $attempt): PaymentStatusResult;

    public function verifyNotification(Request $request): VerifiedPaymentEvent;
}
