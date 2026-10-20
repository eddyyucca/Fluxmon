<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('viewer')->after('email');
            $table->boolean('all_routers')->default(true)->after('role');
        });

        // Everyone who existed before roles were introduced was a full admin.
        DB::table('users')->update(['role' => 'admin']);

        Schema::create('router_user', function (Blueprint $table) {
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['router_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'all_routers']);
        });
    }
};
