<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_cambio_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->foreignId('solicitante_persona_id')->comment('Tesorero o cobrador que solicita la modificación')->constrained('personas')->cascadeOnUpdate();
            $table->foreignId('chofer_id')->comment('Chofer que fue marcado en el cobro erróneo')->constrained('choferes')->cascadeOnUpdate();
            $table->foreignId('jefe_persona_id')->nullable()->comment('Jefe de grupo notificado')->constrained('personas')->nullOnDelete();
            $table->text('motivo')->comment('Justificación de por qué se requiere cambiar o anular el pago');
            $table->string('estado', 20)->default('PENDIENTE')->comment('PENDIENTE, APROBADO, RECHAZADO');
            $table->text('respuesta_observacion')->nullable()->comment('Motivo o comentario del chofer al responder');
            $table->foreignId('respondido_por_persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->dateTime('fecha_respuesta')->nullable();
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_cambio_pago');
    }
};
