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
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnUpdate();
            $table->foreignId('jefe_persona_id')->comment('Persona/Jefe de grupo que creo la obligacion')->constrained('personas')->cascadeOnUpdate();
            $table->foreignId('tipo_obligacion_id')->nullable()->constrained('tipos_obligacion')->cascadeOnUpdate();
            
            $table->string('tipo_categoria', 20)->default('MENSUAL')->comment('MENSUAL o AYUDA');
            $table->string('concepto', 255);
            $table->decimal('monto_individual', 10, 2)->comment('Monto a cobrar por chofer');
            $table->decimal('monto_total_esperado', 10, 2)->default(0.00)->comment('Monto total a recaudar en el grupo');
            
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->comment('Para mensual es 1 mes exacto desde fecha_inicio');
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
