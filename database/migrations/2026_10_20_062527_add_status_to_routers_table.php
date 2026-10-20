<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->boolean('is_online')->nullable()->after('timeout');
            $table->timestamp('last_checked_at')->nullable()->after('is_online');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'last_checked_at']);
        });
    }
};
