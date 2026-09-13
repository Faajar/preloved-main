<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentFulfillment;
use App\Models\PaymentRefund;
use App\PaymentAttemptStatus;
use App\PaymentFulfillmentStatus;
use App\PaymentRefundKind;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentLifecycleDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_lifecycle_tables_have_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('payment_attempts', [
            'payment_id', 'sequence', 'provider', 'status', 'provider_request_id', 'invoice_number',
            'provider_reference', 'checkout_url', 'expires_at', 'request_payload', 'request_body',
            'request_payload_hash', 'request_count', 'first_requested_at', 'last_reconciled_at',
            'last_provider_status', 'last_provider_error',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_fulfillments', [
            'payment_id', 'status', 'boost_duration_hours', 'fulfilled_at', 'reversed_at',
            'last_failure', 'attempt_count', 'next_retry_at',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_refunds', [
            'payment_id', 'payment_attempt_id', 'provider', 'provider_refund_reference',
            'provider_status', 'kind', 'amount', 'currency', 'reason', 'requires_review',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_webhook_events', [
            'payment_attempt_id', 'provider_status', 'processing_status', 'payload',
            'processing_attempts', 'next_retry_at',
        ]));
    }

    public function test_payment_exposes_separated_lifecycle_relationships_and_casts(): void
    {
        $payment = Payment::factory()->forBoost(36)->create();
        $firstAttempt = PaymentAttempt::factory()->for($payment)->create();
        $secondAttempt = PaymentAttempt::factory()->for($payment)->create(['sequence' => 2]);
        $refund = PaymentRefund::factory()->forAttempt($secondAttempt)->create([
            'kind' => PaymentRefundKind::Partial,
            'requires_review' => true,
        ]);

        $this->assertTrue($payment->attempts->contains($firstAttempt));
        $this->assertTrue($payment->attempts->contains($secondAttempt));
        $this->assertTrue($payment->currentAttempt->is($secondAttempt));
        $this->assertSame(PaymentAttemptStatus::Initialized, $secondAttempt->status);
        $this->assertSame(PaymentFulfillmentStatus::Pending, $payment->fulfillment->status);
        $this->assertSame(36, $payment->fulfillment->boost_duration_hours);
        $this->assertTrue($payment->refunds->first()->is($refund));
        $this->assertSame(PaymentRefundKind::Partial, $refund->kind);
        $this->assertTrue($refund->requires_review);
    }

    public function test_payment_accepts_only_one_fulfillment_record(): void
    {
        $payment = Payment::factory()->create();

        $this->expectException(QueryException::class);

        PaymentFulfillment::factory()->for($payment)->create();
    }

    public function test_attempt_sequence_and_provider_identifiers_are_unique(): void
    {
        $payment = Payment::factory()->create();
        PaymentAttempt::factory()->for($payment)->create([
            'provider_request_id' => 'request-unique',
            'invoice_number' => 'PIN-UNIQUE',
        ]);

        $this->expectException(QueryException::class);

        PaymentAttempt::factory()->for($payment)->create([
            'provider_request_id' => 'request-other',
            'invoice_number' => 'PIN-OTHER',
        ]);
    }

    public function test_checkout_usability_is_derived_from_current_attempt(): void
    {
        $payment = Payment::factory()->create();
        PaymentAttempt::factory()->for($payment)->checkoutCreated()->create();

        $this->assertTrue($payment->fresh()->hasUsableCheckout());

        $payment->currentAttempt->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($payment->fresh()->hasUsableCheckout());
    }
}
