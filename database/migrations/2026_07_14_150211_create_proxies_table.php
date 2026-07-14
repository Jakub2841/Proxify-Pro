<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('address', 45);
            $table->unsignedSmallInteger('port');
            $table->string('protocol');
            $table->string('country', 2);
            $table->boolean('google_pass')->default(false);
            $table->boolean('cloudflare_pass')->default(false);
            $table->unsignedMediumInteger('latency_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('protocol');
            $table->index('country');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
