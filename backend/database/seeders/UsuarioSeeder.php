<?php

namespace Database\Seeders;

use App\Models\Auto;
use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Grupo;
use App\Models\Lugar;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rol;
use App\Models\TipoObligacion;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Crear roles, personas, usuarios, choferes y datos de prueba.
     */
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // ROLES DEL SINDICATO
        // ═══════════════════════════════════════════════════════════════
        $rolesData = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'Jefe de Grupo', 'descripcion' => 'Responsable de un grupo de choferes y vehículos'],
            ['nombre' => 'Inspector',     'descripcion' => 'Control de asistencia y emisión de multas'],
            ['nombre' => 'Tesorero',      'descripcion' => 'Gestión de cobros, pagos y obligaciones financieras'],
            ['nombre' => 'Chofer',        'descripcion' => 'Conductor afiliado al sindicato'],
        ];

        $roles = [];
        foreach ($rolesData as $data) {
            $roles[$data['nombre']] = Rol::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => true, 'usuario_audit' => 'system'])
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // GRUPOS DE TRABAJO
        // ═══════════════════════════════════════════════════════════════
        $gruposData = [
            ['nombre' => 'Grupo A', 'descripcion' => 'Ruta Norte - Centro'],
            ['nombre' => 'Grupo B', 'descripcion' => 'Ruta Sur - Centro'],
            ['nombre' => 'Grupo C', 'descripcion' => 'Ruta Este - Centro'],
        ];

        $grupos = [];
        foreach ($gruposData as $data) {
            $grupos[$data['nombre']] = Grupo::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => true, 'usuario_audit' => 'system'])
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // LUGARES DE ASISTENCIA
        // ═══════════════════════════════════════════════════════════════
        $lugaresData = ['Terminal Norte', 'Terminal Sur', 'Parada Central', 'Parada Mercado'];

        foreach ($lugaresData as $nombre) {
            Lugar::firstOrCreate(
                ['nombre' => $nombre],
                ['estado' => true, 'usuario_audit' => 'system']
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // TIPOS DE OBLIGACIÓN
        // ═══════════════════════════════════════════════════════════════
        $tiposObligacion = [
            ['nombre' => 'Cuota Mensual',     'descripcion' => 'Cuota mensual ordinaria del sindicato'],
            ['nombre' => 'Cuota Extraordinaria', 'descripcion' => 'Cuota por eventos o necesidades especiales'],
            ['nombre' => 'Aporte Sindical',   'descripcion' => 'Aporte obligatorio al fondo sindical'],
        ];

        foreach ($tiposObligacion as $data) {
            TipoObligacion::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => true, 'usuario_audit' => 'system'])
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // PERSONA + USUARIO ADMINISTRADOR
        // ═══════════════════════════════════════════════════════════════
        $personaAdmin = Persona::firstOrCreate(
            ['ci' => '0000001'],
            [
                'primer_nombre'    => 'Admin',
                'primer_apellido'  => 'Sistema',
                'estado'           => true,
                'usuario_audit'    => 'system',
            ]
        );

        $usuarioAdmin = Usuario::firstOrCreate(
            ['username' => 'admin'],
            [
                'persona_id'    => $personaAdmin->id,
                'password'      => Hash::make('password'),
                'estado'        => true,
                'usuario_audit' => 'system',
            ]
        );

        $usuarioAdmin->roles()->syncWithoutDetaching([
            $roles['Administrador']->id => ['estado' => true, 'usuario_audit' => 'system'],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // PERSONAS Y CHOFERES DE PRUEBA
        // ═══════════════════════════════════════════════════════════════
        $choferesData = [
            [
                'persona' => ['primer_nombre' => 'Juan',   'segundo_nombre' => 'Carlos', 'primer_apellido' => 'Mamani',  'segundo_apellido' => 'Quispe',  'ci' => '4521678', 'celular' => '71234567', 'direccion' => 'Av. 6 de Agosto #123'],
                'chofer'  => ['fecha_ingreso' => '2020-03-15'],
                'roles'   => ['Chofer'],
                'grupo'   => 'Grupo A',
            ],
            [
                'persona' => ['primer_nombre' => 'Pedro',  'segundo_nombre' => null,      'primer_apellido' => 'Condori', 'segundo_apellido' => 'Huanca',  'ci' => '5632789', 'celular' => '72345678', 'direccion' => 'Calle Comercio #456'],
                'chofer'  => ['fecha_ingreso' => '2019-07-01'],
                'roles'   => ['Chofer', 'Tesorero'],  // Un chofer puede ser también tesorero
                'grupo'   => 'Grupo A',
            ],
            [
                'persona' => ['primer_nombre' => 'Miguel', 'segundo_nombre' => 'Ángel',   'primer_apellido' => 'Flores',  'segundo_apellido' => 'Rojas',   'ci' => '6743890', 'celular' => '73456789', 'direccion' => 'Zona San Pedro #789'],
                'chofer'  => ['fecha_ingreso' => '2021-01-10'],
                'roles'   => ['Chofer', 'Inspector'],
                'grupo'   => 'Grupo B',
            ],
            [
                'persona' => ['primer_nombre' => 'Roberto','segundo_nombre' => null,       'primer_apellido' => 'Choque',  'segundo_apellido' => 'Limachi', 'ci' => '7854901', 'celular' => '74567890', 'direccion' => 'Villa Fátima #321'],
                'chofer'  => ['fecha_ingreso' => '2018-05-20'],
                'roles'   => ['Chofer', 'Jefe de Grupo'],
                'grupo'   => 'Grupo B',
            ],
            [
                'persona' => ['primer_nombre' => 'Carlos', 'segundo_nombre' => 'Eduardo',  'primer_apellido' => 'Ticona', 'segundo_apellido' => 'Apaza',   'ci' => '8965012', 'celular' => '75678901', 'direccion' => 'El Alto, Zona 16 de Julio'],
                'chofer'  => ['fecha_ingreso' => '2022-09-01'],
                'roles'   => ['Chofer'],
                'grupo'   => 'Grupo C',
            ],
        ];

        $choferesCreados = [];

        foreach ($choferesData as $data) {
            // Persona
            $persona = Persona::firstOrCreate(
                ['ci' => $data['persona']['ci']],
                array_merge($data['persona'], ['estado' => true, 'usuario_audit' => 'system'])
            );

            // Chofer
            $chofer = Chofer::firstOrCreate(
                ['persona_id' => $persona->id],
                array_merge($data['chofer'], ['estado' => true, 'usuario_audit' => 'system'])
            );

            // Usuario para cada chofer
            $username = strtolower($data['persona']['primer_nombre']) . '.' . strtolower($data['persona']['primer_apellido']);
            $usuario = Usuario::firstOrCreate(
                ['username' => $username],
                [
                    'persona_id'    => $persona->id,
                    'password'      => Hash::make('password'),
                    'estado'        => true,
                    'usuario_audit' => 'system',
                ]
            );

            // Asignar roles
            $rolIds = [];
            foreach ($data['roles'] as $rolNombre) {
                $rolIds[$roles[$rolNombre]->id] = ['estado' => true, 'usuario_audit' => 'system'];
            }
            $usuario->roles()->syncWithoutDetaching($rolIds);

            $choferesCreados[] = [
                'chofer' => $chofer,
                'grupo'  => $data['grupo'],
                'persona' => $persona,
            ];
        }

        // ═══════════════════════════════════════════════════════════════
        // PROPIETARIOS Y VEHÍCULOS DE PRUEBA
        // ═══════════════════════════════════════════════════════════════

        // Algunos choferes son también propietarios de sus vehículos
        $vehiculosData = [
            ['propietario_idx' => 0, 'placa' => '1234-ABC', 'marca' => 'Toyota',    'modelo' => 'Hiace',       'gestion' => 2018, 'grupo' => 'Grupo A'],
            ['propietario_idx' => 1, 'placa' => '2345-BCD', 'marca' => 'Hyundai',   'modelo' => 'H1',          'gestion' => 2020, 'grupo' => 'Grupo A'],
            ['propietario_idx' => 3, 'placa' => '3456-CDE', 'marca' => 'Mercedes',  'modelo' => 'Sprinter',    'gestion' => 2019, 'grupo' => 'Grupo B'],
            ['propietario_idx' => 4, 'placa' => '4567-DEF', 'marca' => 'Nissan',    'modelo' => 'Urvan',       'gestion' => 2021, 'grupo' => 'Grupo C'],
        ];

        foreach ($vehiculosData as $vData) {
            $choferInfo = $choferesCreados[$vData['propietario_idx']];

            // Registrar como propietario
            $propietario = Propietario::firstOrCreate(
                ['persona_id' => $choferInfo['persona']->id],
                [
                    'fecha_registro' => $choferInfo['chofer']->fecha_ingreso,
                    'estado'         => true,
                    'usuario_audit'  => 'system',
                ]
            );

            // Crear vehículo
            $auto = Auto::firstOrCreate(
                ['placa' => $vData['placa']],
                [
                    'propietario_id' => $propietario->id,
                    'marca'          => $vData['marca'],
                    'modelo'         => $vData['modelo'],
                    'gestion'        => $vData['gestion'],
                    'estado'         => true,
                    'usuario_audit'  => 'system',
                ]
            );

            // Asignar chofer al auto en su grupo
            ChoferAuto::firstOrCreate(
                ['auto_id' => $auto->id, 'chofer_id' => $choferInfo['chofer']->id],
                [
                    'grupo_id'       => $grupos[$vData['grupo']]->id,
                    'estado'         => true,
                    'usuario_audit'  => 'system',
                ]
            );
        }

        // Miguel (inspector, Grupo B) también conduce el Sprinter de Roberto
        $miguelChofer = $choferesCreados[2]['chofer'];
        $autoRoberto = Auto::where('placa', '3456-CDE')->first();
        if ($miguelChofer && $autoRoberto) {
            ChoferAuto::firstOrCreate(
                ['auto_id' => $autoRoberto->id, 'chofer_id' => $miguelChofer->id],
                [
                    'grupo_id'       => $grupos['Grupo B']->id,
                    'estado'         => true,
                    'usuario_audit'  => 'system',
                ]
            );
        }
    }
}
