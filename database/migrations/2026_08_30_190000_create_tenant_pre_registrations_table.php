<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_pre_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('status');

            $table->string('name')->nullable();
            $table->string('document')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->unsignedInteger('resident_count')->nullable();

            $table->timestamp('invited_at');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_note')->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_pre_registrations');
    }
};
