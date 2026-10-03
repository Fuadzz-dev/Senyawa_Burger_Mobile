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
        Schema::table('pesanan', function (Blueprint $table) {
            if (Schema::hasColumn('pesanan', 'tipe_order')) {
                $table->dropColumn('tipe_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            if (! Schema::hasColumn('pesanan', 'tipe_order')) {
                $table->enum('tipe_order', ['dine_in', 'take_away', 'delivery'])->default('dine_in')->after('nama');
            }
        });
    }
};
