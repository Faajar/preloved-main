<?php

namespace App;

enum PaymentWebhookProcessingStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Rejected = 'rejected';
    case Unhandled = 'unhandled';
    case Failed = 'failed';
}
