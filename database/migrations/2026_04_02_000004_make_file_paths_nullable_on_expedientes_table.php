<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('documento_path')->nullable()->change();
            $table->string('identificacion_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('documento_path')->nullable(false)->change();
            $table->string('identificacion_path')->nullable(false)->change();
        });
    }
};