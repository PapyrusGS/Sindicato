<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')->constrained('propietarios')->cascadeOnUpdate();
            $table->string('placa', 100);
            $table->string('modelo', 100);
            $table->string('marca', 100);
            $table->integer('gestion');
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autos');
    }
};
