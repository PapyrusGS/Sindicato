<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnUpdate();
            $table->foreignId('tipo_obligacion_id')->constrained('tipos_obligacion')->cascadeOnUpdate();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_creacion')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligaciones');
    }
};
