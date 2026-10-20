<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Business data for a hotspot profile that RouterOS itself cannot store.
        Schema::create('profile_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // hotspot profile name on the router
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('validity_minutes')->nullable(); // wall-clock, counted from first use
            $table->string('expire_action', 10)->default('remove'); // remove | disable
            $table->timestamps();

            $table->unique(['router_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_metas');
    }
};
