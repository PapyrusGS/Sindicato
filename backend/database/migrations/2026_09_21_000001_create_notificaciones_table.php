<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->string('tipo', 50)->default('INFO')->comment('SOLICITUD_CAMBIO_PAGO, INFO, ALERTA, SISTEMA');
            $table->json('data')->nullable()->comment('IDs y metadata adicional: solicitud_id, pago_id, etc.');
            $table->boolean('leido')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
