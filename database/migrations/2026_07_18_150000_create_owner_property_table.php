<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Um imovel pode ter mais de um proprietario, entao a relacao
     * properties.owner_id (1:N) vira uma tabela pivot (N:N).
     */
    public function up(): void
    {
        Schema::create('owner_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'property_id']);
        });

        DB::table('properties')
            ->whereNotNull('owner_id')
            ->select('id', 'owner_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $property): void {
                DB::table('owner_property')->insert([
                    'owner_id' => $property->owner_id,
                    'property_id' => $property->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('status')->constrained('owners')->nullOnDelete();
        });

        DB::table('owner_property')
            ->orderBy('property_id')
            ->orderBy('id')
            ->get()
            ->unique('property_id')
            ->each(function (object $pivot): void {
                DB::table('properties')
                    ->where('id', $pivot->property_id)
                    ->update(['owner_id' => $pivot->owner_id]);
            });

        Schema::dropIfExists('owner_property');
    }
};
