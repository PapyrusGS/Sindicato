<?php

namespace Tests\Feature;

use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Notificacion;
use App\Models\Obligacion;
use App\Models\ObligacionChofer;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\SolicitudCambioPago;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CobroFlujoAnulacionTest extends TestCase
{
    use DatabaseTransactions;

    protected Usuario $adminUser;
    protected Usuario $tesoreroUser;
    protected Usuario $choferUser;
    protected Chofer $choferModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = Usuario::where('username', 'admin')->first() ?? Usuario::factory()->create();

        // Obtener un Chofer con usuario asociado
        $this->choferModel = Chofer::whereHas('persona.usuario')->first();
        $this->choferUser = $this->choferModel->persona->usuario;

        // Obtener un Tesorero distinto al chofer
        $this->tesoreroUser = Usuario::whereHas('roles', fn($r) => $r->where('nombre', 'Tesorero'))
            ->where('persona_id', '!=', $this->choferModel->persona_id)
            ->first() ?? $this->adminUser;
    }

    /**
     * Test 1: Anulación directa dentro de los 90 segundos.
     */
    public function test_anulacion_directa_dentro_de_ventana_de_gracia(): void
    {
        // Crear un pago reciente (hace 10 segundos)
        $pago = Pago::create([
            'chofer_id'           => $this->choferModel->id,
            'cobrador_persona_id' => $this->tesoreroUser->persona_id,
            'monto_total'         => 100.00,
            'fecha_pago'          => now(),
            'metodo_pago'         => 'EFECTIVO',
            'estado'              => true,
        ]);

        $this->actingAs($this->tesoreroUser, 'sanctum');

        $response = $this->postJson("/api/cobros/{$pago->id}/anular-inmediato");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $pago->refresh();
        $this->assertFalse((bool)$pago->estado);
        $this->assertStringContainsString('ANULACIÓN DIRECTA EN VENTANA DE GRACIA', $pago->observacion);
    }

    /**
     * Test 2: Intento de anulación directa fallida tras expirar el tiempo de gracia (> 90 segundos).
     */
    public function test_anulacion_directa_falla_si_excede_90_segundos(): void
    {
        // Crear un pago y envejecerlo 5 minutos (300 segundos)
        $pago = Pago::create([
            'chofer_id'           => $this->choferModel->id,
            'cobrador_persona_id' => $this->tesoreroUser->persona_id,
            'monto_total'         => 100.00,
            'fecha_pago'          => now()->subMinutes(5),
            'metodo_pago'         => 'EFECTIVO',
            'estado'              => true,
        ]);
        Pago::where('id', $pago->id)->update(['created_at' => now()->subMinutes(5)]);
        $pago->refresh();

        $this->actingAs($this->tesoreroUser, 'sanctum');

        $response = $this->postJson("/api/cobros/{$pago->id}/anular-inmediato");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertStringContainsString('tiempo de gracia de 1:30 minutos ha expirado', $response->json('message'));
    }

    /**
     * Test 3: Solicitar cambio tras expirar tiempo de gracia genera notificación.
     */
    public function test_solicitar_cambio_crea_solicitud_y_notificacion(): void
    {
        $pago = Pago::create([
            'chofer_id'           => $this->choferModel->id,
            'cobrador_persona_id' => $this->tesoreroUser->persona_id,
            'monto_total'         => 150.00,
            'fecha_pago'          => now()->subMinutes(10),
            'metodo_pago'         => 'EFECTIVO',
            'estado'              => true,
            'created_at'          => now()->subMinutes(10),
            'updated_at'          => now()->subMinutes(10),
        ]);

        $this->actingAs($this->tesoreroUser, 'sanctum');

        $response = $this->postJson("/api/cobros/{$pago->id}/solicitar-cambio", [
            'motivo' => 'Error de chofer al registrar en caja, se marcó por equivocación.',
        ]);

        $response->assertStatus(201);
        $solicitudId = $response->json('data.id');

        $this->assertDatabaseHas('solicitudes_cambio_pago', [
            'id'      => $solicitudId,
            'pago_id' => $pago->id,
            'estado'  => 'PENDIENTE',
        ]);

        // Verificar que el chofer recibió la notificación
        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $this->choferUser->id,
            'tipo'       => 'SOLICITUD_CAMBIO_PAGO',
        ]);
    }

    /**
     * Test 4: El chofer responde y ACEPTA la solicitud, anulando el pago y notificando al tesorero.
     */
    public function test_chofer_acepta_solicitud_y_anula_pago(): void
    {
        $pago = Pago::create([
            'chofer_id'           => $this->choferModel->id,
            'cobrador_persona_id' => $this->tesoreroUser->persona_id,
            'monto_total'         => 200.00,
            'fecha_pago'          => now()->subMinutes(20),
            'metodo_pago'         => 'EFECTIVO',
            'estado'              => true,
            'created_at'          => now()->subMinutes(20),
            'updated_at'          => now()->subMinutes(20),
        ]);

        $solicitud = SolicitudCambioPago::create([
            'pago_id'                => $pago->id,
            'solicitante_persona_id' => $this->tesoreroUser->persona_id,
            'chofer_id'              => $this->choferModel->id,
            'motivo'                 => 'Equivocación de chofer',
            'estado'                 => 'PENDIENTE',
        ]);

        // Chofer responde
        $this->actingAs($this->choferUser, 'sanctum');

        $response = $this->postJson("/api/solicitudes-cambio/{$solicitud->id}/responder", [
            'accion'      => 'ACEPTAR',
            'observacion' => 'Efectivamente yo no realicé este pago.',
        ]);

        $response->assertStatus(200);

        $solicitud->refresh();
        $this->assertEquals('APROBADO', $solicitud->estado);

        $pago->refresh();
        $this->assertFalse((bool)$pago->estado);
        $this->assertStringContainsString('ANULACIÓN APROBADA POR CHOFER', $pago->observacion);

        // Tesorero notificado
        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $this->tesoreroUser->id,
            'tipo'       => 'INFO',
        ]);
    }

    /**
     * Test 5: API de notificaciones lista y marca como leídas.
     */
    public function test_notificaciones_api_listar_y_marcar(): void
    {
        Notificacion::create([
            'usuario_id' => $this->choferUser->id,
            'titulo'     => 'Aviso de prueba',
            'mensaje'    => 'Mensaje test',
            'tipo'       => 'INFO',
            'leido'      => false,
        ]);

        $this->actingAs($this->choferUser, 'sanctum');

        $response = $this->getJson('/api/notificaciones');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('data.no_leidas_count'));

        $notifId = $response->json('data.notificaciones.0.id');
        $patchRes = $this->patchJson("/api/notificaciones/{$notifId}/leer");
        $patchRes->assertStatus(200);

        $this->assertDatabaseHas('notificaciones', [
            'id'    => $notifId,
            'leido' => true,
        ]);
    }
}
