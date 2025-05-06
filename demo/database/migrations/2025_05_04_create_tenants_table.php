<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('lookup')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('shard');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('lookup')->dropIfExists('tenants');
    }
};
