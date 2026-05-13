<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('position');
            $table->string('full_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_leaders');
    }
};
