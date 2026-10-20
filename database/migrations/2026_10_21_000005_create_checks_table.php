<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reachability checks: ping run by the router itself, or a TCP connect run by Fluxmon.
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20); // router_ping | tcp
            $table->string('target');
            $table->unsignedSmallInteger('port')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('last_status')->nullable();
            $table->double('last_rtt')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_change_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
