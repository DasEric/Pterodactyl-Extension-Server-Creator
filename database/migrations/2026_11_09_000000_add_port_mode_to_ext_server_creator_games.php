<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_server_creator_games', function (Blueprint $table): void {
            // Existing games retain their positional, consecutive assignments.
            $table->string('port_mode', 16)->default('consecutive');
        });
    }

    public function down(): void
    {
        Schema::table('ext_server_creator_games', function (Blueprint $table): void {
            $table->dropColumn('port_mode');
        });
    }
};
