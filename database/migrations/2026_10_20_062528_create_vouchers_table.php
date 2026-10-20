<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_batch_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->string('password');
            $table->string('profile');
            $table->decimal('price', 12, 2)->default(0); // frozen at generation time
            $table->string('status', 10)->default('unused'); // unused | used | expired
            $table->timestamp('first_used_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
