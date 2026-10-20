<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 20); // manual | scheduled | pre-change
            $table->string('hash', 64);
            $table->unsignedInteger('size');
            $table->longText('content');
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['router_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_backups');
    }
};
