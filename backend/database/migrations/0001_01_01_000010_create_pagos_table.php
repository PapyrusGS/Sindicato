<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('choferes')->cascadeOnUpdate();
            $table->foreignId('cobrador_persona_id')->comment('Persona del tesorero o jefe de grupo que cobro')->constrained('personas')->cascadeOnUpdate();
            $table->decimal('monto_total', 10, 2);
            $table->dateTime('fecha_pago');
            $table->string('metodo_pago', 20)->default('EFECTIVO')->comment('EFECTIVO o TRANSFERENCIA_QR');
            $table->text('observacion')->nullable();
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
