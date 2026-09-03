<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentAttemptStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete();


            $table->unsignedInteger('attempt_no');
            $table->dateTime('last_event_occurred_at')->nullable();
            $table->unsignedInteger('state_version')
                ->default(1);


            $table->string('provider');
            $table->string('provider_payment_id')->nullable();
            $table->string('provider_invoice_id')->nullable();

            $table->string('status')->default(PaymentAttemptStatus::PENDING->value);
            $table->timestamps();
            $table->index(['purchase_id', 'status']);


                        $table->unique([
                            'purchase_id',
                            'attempt_no',
                        ]);

             $table->unique([
                 'provider',
                 'provider_invoice_id',
             ]);
            $table->unique(['provider', 'provider_payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
