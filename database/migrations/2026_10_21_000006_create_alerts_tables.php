<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->nullable()->constrained()->cascadeOnDelete(); // null = every router
            $table->string('name');
            $table->string('metric', 40); // cpu | memory | temperature
            $table->string('operator', 1); // > or <
            $table->double('threshold');
            $table->unsignedSmallInteger('duration_minutes')->default(5);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // rule | router_offline | check_down
            $table->string('subject', 40); // dedupe key: "rule:5", "router", "check:3"
            $table->string('title');
            $table->double('value')->nullable();
            $table->string('state', 10); // firing | resolved
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();

            $table->index(['router_id', 'subject', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_events');
        Schema::dropIfExists('alert_rules');
    }
};
