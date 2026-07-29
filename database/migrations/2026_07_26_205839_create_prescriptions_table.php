<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained()->onDelete('restrict');
            $table->string('dosage');      // ለምሳሌ: "500mg"
            $table->string('frequency');   // ለምሳሌ: "2x Daily"
            $table->string('duration');    // ለምሳሌ: "5 Days"
            $table->enum('status', ['pending', 'dispensed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};