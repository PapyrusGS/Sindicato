<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->string('tabla_nombre', 100)->comment('Nombre de la tabla afectada');
            $table->unsignedBigInteger('registro_id')->comment('ID del registro modificado');
            $table->string('accion', 50)->comment('Accion realizada: CREACION, MODIFICACION, DESACTIVACION, REACTIVACION, ELIMINACION');
            $table->string('campo', 100)->nullable()->comment('Atributo de la tabla que cambio');
            $table->text('valor_anterior')->nullable()->comment('Valor previo antes del cambio');
            $table->text('valor_nuevo')->nullable()->comment('Nuevo valor asignado al campo');
            $table->foreignId('persona_id')->nullable()->constrained('personas')->cascadeOnUpdate()->comment('ID de la persona que realizo la accion');
            $table->dateTime('fecha_a')->comment('Fecha y hora exacta de la accion');
            $table->string('direccion_ip', 45)->nullable()->comment('Direccion IP del cliente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
