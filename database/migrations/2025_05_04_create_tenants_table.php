<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('lookup')->create('tenants', function (Blueprint $table) {
            $table->id();                  // tenant ID
            $table->string('shard');      // имя шарда (например, shard_0, shard_1)
            $table->timestamps();         // created_at / updated_at (опционально)
        });
    }

    public function down(): void
    {
        Schema::connection('lookup')->dropIfExists('tenants');
    }
};
