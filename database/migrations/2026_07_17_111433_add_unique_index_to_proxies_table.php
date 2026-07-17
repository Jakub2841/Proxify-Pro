<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('
                DELETE p1 FROM proxies p1
                INNER JOIN proxies p2
                WHERE p1.id > p2.id AND p1.address = p2.address AND p1.port = p2.port
            ');
        } else {
            DB::statement('
                DELETE FROM proxies WHERE id NOT IN (
                    SELECT MIN(id) FROM proxies GROUP BY address, port
                )
            ');
        }

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
