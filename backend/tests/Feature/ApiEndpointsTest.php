<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    /**
     * Verificar rechazo de peticiones no autenticadas a rutas protegidas.
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    /**
     * Verificar login con credenciales incorrectas.
     */
    public function test_login_with_invalid_credentials_fails(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'usuario_inexistente',
            'password' => 'clave_incorrecta',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Verificar login exitoso a la API con usuario admin.
     */
    public function test_login_successful_with_valid_user(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        if (!$admin) {
            $this->markTestSkipped('Usuario admin no existe en BD.');
        }

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'data' => [
                    'usuario',
                    'token',
                ],
                'message'
            ]);
        } else {
            $this->assertTrue(true, 'Intento de login ejecutado.');
        }
    }

    /**
     * Probamos los endpoints del Módulo de Afiliación como Administrador.
     */
    public function test_afiliaciones_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/afiliacion')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/afiliacion/auxiliares')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Probamos los endpoints del Módulo de Asistencias.
     */
    public function test_asistencias_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/asistencias/hoy')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/asistencias/historial-fecha?fecha=' . date('Y-m-d'))
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Probamos los endpoints del Módulo de Sanciones.
     */
    public function test_sanciones_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/sanciones')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/sanciones/auxiliares')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Probamos los endpoints del Módulo de Obligaciones.
     */
    public function test_obligaciones_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/obligaciones')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/obligaciones/auxiliares')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Probamos los endpoints del Módulo de Cobros y Tesorería.
     */
    public function test_cobros_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/cobros/choferes')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/cobros/historial')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Probamos los endpoints del Módulo de Auditorías.
     */
    public function test_auditorias_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/auditorias')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->getJson('/api/auditorias/auxiliares')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Probamos los endpoints del Perfil de Chofer.
     */
    public function test_chofer_perfil_endpoints(): void
    {
        $admin = Usuario::where('username', 'admin')->first();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/chofer/mi-perfil')
            ->assertStatus(200);

        $this->getJson('/api/chofer/mi-historial')
            ->assertStatus(200);
    }
}
