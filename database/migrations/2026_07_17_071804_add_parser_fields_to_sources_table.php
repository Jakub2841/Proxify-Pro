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
        Schema::table('sources', function (Blueprint $table) {
            $table->string('parser_type')->default('plain_text')->after('proxy_count');
            $table->json('parser_config')->nullable()->after('parser_type');
            $table->string('default_protocol')->nullable()->after('parser_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn(['parser_type', 'parser_config', 'default_protocol']);
        });
    }
};
