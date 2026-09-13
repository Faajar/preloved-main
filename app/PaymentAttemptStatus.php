<?php

namespace App;

enum PaymentAttemptStatus: string
{
    case Initialized = 'initialized';
    case CheckoutCreated = 'checkout_created';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Expired = 'expired';
    case Ambiguous = 'ambiguous';
}
