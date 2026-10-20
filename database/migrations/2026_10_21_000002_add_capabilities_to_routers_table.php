<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->unsignedSmallInteger('ssh_port')->default(22)->after('port');
            $table->string('ssh_fingerprint', 64)->nullable(); // pinned on first connect
            $table->string('identity')->nullable();
            $table->string('ros_version', 40)->nullable();
            $table->unsignedTinyInteger('ros_major')->nullable();
            $table->string('board', 80)->nullable();
            $table->string('architecture', 40)->nullable();
            $table->json('capabilities')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['ssh_port', 'ssh_fingerprint', 'identity', 'ros_version', 'ros_major', 'board', 'architecture', 'capabilities']);
        });
    }
};
