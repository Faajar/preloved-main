<?php

namespace App\Http\Controllers;

use App\Payments\PaymentWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class DokuPaymentNotificationController extends Controller
{
    public function __invoke(Request $request, PaymentWebhookHandler $handler): Response
    {
        try {
            $handler->handle($request);
        } catch (Throwable $exception) {
            report($exception);

            return response(status: 400);
        }

        return response(status: 200);
    }
}
