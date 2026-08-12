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
        // 1. Create purchases (batches) table
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('batch_number');
            $table->integer('quantity_purchased');
            $table->integer('quantity_sold')->default(0);
            $table->integer('remaining_quantity');
            $table->date('purchase_date');
            $table->date('expiry_date');
            $table->timestamps();
        });

        // 2. Update medicines table to add category, reorder_level, and relax legacy columns
        Schema::table('medicines', function (Blueprint $table) {
            if (!Schema::hasColumn('medicines', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
            if (!Schema::hasColumn('medicines', 'reorder_level')) {
                $table->integer('reorder_level')->default(10)->after('price_per_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');

        Schema::table('medicines', function (Blueprint $table) {
            if (Schema::hasColumn('medicines', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('medicines', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }
        });
    }
};
