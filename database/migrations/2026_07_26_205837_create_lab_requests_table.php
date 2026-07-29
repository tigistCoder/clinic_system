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
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('test_name');
            $table->decimal('price', 8, 2);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('result')->nullable();
            $table->foreignId('lab_tech_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending_payment', 'paid_pending_test', 'completed'])->default('pending_payment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_requests');
    }
};