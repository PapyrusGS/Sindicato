<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligacion_choferes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obligacion_id')->constrained('obligaciones')->cascadeOnDelete();
            $table->foreignId('chofer_id')->constrained('choferes')->cascadeOnUpdate();
            $table->decimal('monto_asignado', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0.00);
            $table->string('estado_pago', 20)->default('PENDIENTE')->comment('PENDIENTE, PARCIAL, PAGADO');
            $table->dateTime('fecha_pago')->nullable();
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligacion_choferes');
    }
};
