<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('choferes')->cascadeOnUpdate();
            $table->foreignId('inspector_id')->comment('Chofer que actúa como inspector')->constrained('choferes')->cascadeOnUpdate();
            $table->foreignId('lugar_id')->nullable()->constrained('lugares')->cascadeOnUpdate();
            
            $table->string('tipo_sancion', 20)->default('ECONOMICA')->comment('ECONOMICA o CASTIGO');
            $table->text('motivo')->comment('Infraccion cometida');
            $table->text('sancion_detalle')->nullable()->comment('Detalle de la sancion o tiempo de parqueo');
            $table->decimal('monto', 10, 2)->default(0.00)->comment('Monto en Bs si es sancion economica');
            $table->string('estado_pago', 20)->default('NO_APLICA')->comment('PENDIENTE, PAGADO, NO_APLICA');
            
            $table->dateTime('fecha_infraccion');
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multas');
    }
};
