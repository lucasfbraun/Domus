<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * StoreOwnerRequest has always validated `email` and `phone` as
     * nullable — an owner is a contact record and doesn't necessarily have
     * either — but the columns themselves were never relaxed to match, so a
     * genuinely email-less or phone-less owner crashed with a NOT NULL
     * constraint violation instead of saving.
     */
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
