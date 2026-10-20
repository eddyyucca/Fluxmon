<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(8728);
            $table->boolean('use_ssl')->default(false);
            $table->string('username');
            $table->text('password'); // encrypted via model cast
            $table->string('hotspot_name')->nullable();
            $table->string('dns_name')->nullable();
            $table->string('currency', 8)->default('Rp');
            $table->string('traffic_interface')->default('ether1');
            $table->unsignedSmallInteger('timeout')->default(5);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
