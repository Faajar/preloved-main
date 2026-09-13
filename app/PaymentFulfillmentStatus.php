<?php

namespace App;

enum PaymentFulfillmentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Fulfilled = 'fulfilled';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
