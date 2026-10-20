<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounts that belong to one device. Staff sign in to Fluxmon first, then again to each device with one of these.
        Schema::create('router_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('username', 64);
            $table->string('password'); // hashed
            $table->string('role', 20); // technician | cashier | viewer
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'username']);
        });

        // Fluxmon account roles are now: superadmin, administrator, staff.
        DB::table('users')->where('role', 'admin')->update(['role' => 'superadmin']);
        DB::table('users')->whereIn('role', ['technician', 'cashier', 'viewer'])->update(['role' => 'staff']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        DB::table('users')->whereIn('role', ['administrator', 'staff'])->update(['role' => 'viewer']);
        Schema::dropIfExists('router_accounts');
    }
};
