<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->default('disabled');
            $table->unsignedTinyInteger('retention_count')->default(7);
            $table->unsignedTinyInteger('run_at_hour')->default(3);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
