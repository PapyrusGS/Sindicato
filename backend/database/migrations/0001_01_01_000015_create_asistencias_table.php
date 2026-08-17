<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('choferes')->cascadeOnUpdate();
            $table->foreignId('lugar_id')->constrained('lugares')->cascadeOnUpdate();
            $table->foreignId('inspector_id')->comment('Chofer que actúa como inspector')->constrained('choferes')->cascadeOnUpdate();
            $table->dateTime('fecha_hora')->nullable();
            $table->boolean('asistencia');
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
