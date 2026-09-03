<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
//                $table->string('provider_invoice_id')
//                    ->nullable()
//                    ->after('provider');
//
//                $table->unique([
//                    'provider',
//                    'provider_invoice_id',
//                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::dropColumns('payment_attempts' , 'provider_invoice_id');
    }
};
