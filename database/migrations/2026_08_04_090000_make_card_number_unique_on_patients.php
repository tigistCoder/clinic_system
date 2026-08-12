<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Patient;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up existing duplicate or null card numbers in database before making unique
        $patients = Patient::orderBy('id', 'asc')->get();
        $seen = [];
        $maxNum = 0;

        foreach ($patients as $p) {
            if ($p->card_number && preg_match('/CARD(\d+)/i', $p->card_number, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        foreach ($patients as $p) {
            if (!$p->card_number || in_array($p->card_number, $seen, true)) {
                $maxNum++;
                $newCard = 'CARD' . str_pad($maxNum, 6, '0', STR_PAD_LEFT);
                $p->update(['card_number' => $newCard]);
                $seen[] = $newCard;
            } else {
                $seen[] = $p->card_number;
            }
        }

        // 2. Add unique constraint to card_number column
        Schema::table('patients', function (Blueprint $table) {
            $table->string('card_number')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['card_number']);
        });
    }
};
