<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('deposit_id')
                ->nullable()
                ->after('charge_id')
                ->constrained('deposits')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('charge_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deposit_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('charge_id')->nullable(false)->change();
        });
    }
};
