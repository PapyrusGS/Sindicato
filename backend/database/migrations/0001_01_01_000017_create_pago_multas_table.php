<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\Auditable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_multas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnUpdate();
            $table->foreignId('multa_id')->constrained('multas')->cascadeOnUpdate();
            $table->decimal('monto_abonado', 10, 2);
            $table->boolean('estado')->default(true);
            Auditable::columns($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_multas');
    }
};
