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
            $table->foreignId('inspector_id')->comment('Referencia a persona que actúa como inspector')->constrained('personas')->cascadeOnUpdate();
            $table->decimal('monto', 10, 2);
            $table->text('motivo')->nullable();
            $table->text('sancion')->nullable();
            $table->date('fecha_infraccion')->nullable();
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
