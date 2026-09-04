<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->index('created_at');
            // FK columns used by doesntHave('vitalSign') / doesntHave('consultation').
            // Adjust column names below to match your actual schema
            // (e.g. visit_id on vital_signs / consultations tables).
        });

        Schema::table('vital_signs', function (Blueprint $table) {
            $table->index('visit_id'); // adjust FK column name if different
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->index('visit_id'); // adjust FK column name if different
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('payment_status');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('patients', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('visits', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('vital_signs', fn (Blueprint $table) => $table->dropIndex(['visit_id']));
        Schema::table('consultations', fn (Blueprint $table) => $table->dropIndex(['visit_id']));
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['payment_status']);
        });
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });
    }
};