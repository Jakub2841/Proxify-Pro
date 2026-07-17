<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicates before adding the unique constraint.
        DB::statement('
            DELETE FROM proxies WHERE id NOT IN (
                SELECT MIN(id) FROM proxies GROUP BY address, port
            )
        ');

        Schema::table('proxies', function (Blueprint $table) {
            $table->unique(['address', 'port']);
        });
    }

    public function down(): void
    {
        Schema::table('proxies', function (Blueprint $table) {
            $table->dropUnique(['address', 'port']);
        });
    }
};
