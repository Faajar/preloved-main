<?php

namespace App;

enum PaymentRefundKind: string
{
    case Full = 'full';
    case Partial = 'partial';
}
