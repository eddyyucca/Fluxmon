<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Time series. resolution = seconds per point: 60 raw samples, 3600 hourly rollups.
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 40);
            $table->string('ref', 60)->default(''); // e.g. interface name or check id
            $table->double('value');
            $table->unsignedSmallInteger('resolution')->default(60);
            $table->unsignedInteger('recorded_at');

            $table->index(['router_id', 'metric', 'ref', 'resolution', 'recorded_at'], 'metrics_series_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
