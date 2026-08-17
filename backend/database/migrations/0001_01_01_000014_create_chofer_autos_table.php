<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chofer_autos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auto_id')->constrained('autos')->cascadeOnUpdate();
            $table->foreignId('chofer_id')->constrained('choferes')->cascadeOnUpdate();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnUpdate();
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chofer_autos');
    }
};
