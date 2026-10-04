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
     * Crear roles, paradas, grupos, personas, usuarios, choferes y datos completos de prueba.
     */
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. ROLES DEL SINDICATO
        // ═══════════════════════════════════════════════════════════════
        $rolesData = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'Jefe de Grupo', 'descripcion' => 'Responsable de un grupo de choferes y vehículos'],
            ['nombre' => 'Inspector',     'descripcion' => 'Control de asistencia y emisión de multas'],
            ['nombre' => 'Tesorero',      'descripcion' => 'Gestión de cobros, pagos y obligaciones financieras'],
            ['nombre' => 'Chofer',        'descripcion' => 'Conductor afiliado al sindicato'],
            ['nombre' => 'Propietario',   'descripcion' => 'Dueño de vehículo afiliado'],
        ];

        $roles = [];
        foreach ($rolesData as $data) {
            $roles[$data['nombre']] = Rol::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => true, 'usuarioA' => 'system', 'fechaA' => now()])
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. 4 GRUPOS DE TRABAJO (A, B, C, D)
        // ═══════════════════════════════════════════════════════════════
        $gruposData = [
            ['nombre' => 'Grupo A', 'descripcion' => 'Primer grupo de rotación'],
            ['nombre' => 'Grupo B', 'descripcion' => 'Segundo grupo de rotación'],
            ['nombre' => 'Grupo C', 'descripcion' => 'Tercer grupo de rotación'],
            ['nombre' => 'Grupo D', 'descripcion' => 'Cuarto grupo de rotación'],
        ];

        $grupos = [];
        foreach ($gruposData as $data) {
            $grupos[$data['nombre']] = Grupo::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => true, 'usuarioA' => 'system', 'fechaA' => now()])
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. 4 PARADAS ROTATORIAS
        // ═══════════════════════════════════════════════════════════════
        $paradasData = ['Obelisco', 'Villa Dolores', 'Cruce Villa Adela', 'Satelite', 'Faro Murillo'];

        foreach ($paradasData as $nombre) {
            Lugar::firstOrCreate(
                ['nombre' => $nombre],
                ['estado' => true, 'usuarioA' => 'system', 'fechaA' => now()]
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // 4. TIPOS DE OBLIGACIÓN
        // ═══════════════════════════════════════════════════════════════
        $tiposObligacion = [
            ['nombre' => 'Cuota Mensual',        'descripcion' => 'Cuota mensual ordinaria del sindicato'],
            ['nombre' => 'Cuota Extraordinaria', 'descripcion' => 'Cuota por eventos o necesidades especiales'],
            ['nombre' => 'Aporte Sindical',      'descripcion' => 'Aporte obligatorio al fondo sindical'],
        ];

        foreach ($tiposObligacion as $data) {
            TipoObligacion::firstOrCreate(
                ['nombre' => $data['nombre']],
                array_merge($data, ['estado' => true, 'usuarioA' => 'system', 'fechaA' => now()])
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // 5. USUARIO ADMINISTRADOR
        // ═══════════════════════════════════════════════════════════════
        $personaAdmin = Persona::firstOrCreate(
            ['ci' => '0000001'],
            [
                'primer_nombre'   => 'Admin',
                'primer_apellido' => 'Sistema',
                'estado'          => true,
                'usuarioA'        => 'system',
                'fechaA'          => now(),
            ]
        );

        $usuarioAdmin = Usuario::firstOrCreate(
            ['username' => 'admin'],
            [
                'persona_id' => $personaAdmin->id,
                'password'   => Hash::make('password'),
                'estado'     => true,
                'usuarioA'   => 'system',
                'fechaA'     => now(),
            ]
        );

        $usuarioAdmin->roles()->syncWithoutDetaching([
            $roles['Administrador']->id => ['estado' => true, 'usuarioA' => 'system', 'fechaA' => now()],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // 6. 7 CHOFERES POR CADA GRUPO (28 CHOFERES PROPIETARIOS DE VAGONETAS)
        // ═══════════════════════════════════════════════════════════════
        $todosChoferes = [
            // ─── GRUPO A ───────────────────────────────────────────────
            [
                'grupo' => 'Grupo A',
                'list'  => [
                    ['pn' => 'Roberto', 'pa' => 'Choque',   'ci' => '7854901', 'cel' => '74567890', 'roles' => ['Chofer', 'Jefe de Grupo', 'Propietario'], 'placa' => '1001-AAA', 'marca' => 'Toyota',     'modelo' => 'Ipsum'],
                    ['pn' => 'Miguel',  'pa' => 'Flores',   'ci' => '6743890', 'cel' => '73456789', 'roles' => ['Chofer', 'Inspector', 'Propietario'],     'placa' => '1002-AAA', 'marca' => 'Toyota',     'modelo' => 'Caldina'],
                    ['pn' => 'Pedro',   'pa' => 'Condori',  'ci' => '5632789', 'cel' => '72345678', 'roles' => ['Chofer', 'Tesorero', 'Propietario'],      'placa' => '1003-AAA', 'marca' => 'Nissan',     'modelo' => 'X-Trail'],
                    ['pn' => 'Juan',    'pa' => 'Mamani',   'ci' => '4521678', 'cel' => '71234567', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '1004-AAA', 'marca' => 'Suzuki',     'modelo' => 'Grand Vitara'],
                    ['pn' => 'Carlos',  'pa' => 'Ticona',   'ci' => '8965012', 'cel' => '75678901', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '1005-AAA', 'marca' => 'Toyota',     'modelo' => 'Probox'],
                    ['pn' => 'Mario',   'pa' => 'Quispe',   'ci' => '1122334', 'cel' => '76112233', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '1006-AAA', 'marca' => 'Mitsubishi', 'modelo' => 'Outlander'],
                    ['pn' => 'Luis',    'pa' => 'Apaza',    'ci' => '2233445', 'cel' => '77223344', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '1007-AAA', 'marca' => 'Hyundai',    'modelo' => 'Tucson'],
                ],
            ],

            // ─── GRUPO B ───────────────────────────────────────────────
            [
                'grupo' => 'Grupo B',
                'list'  => [
                    ['pn' => 'Raúl',    'pa' => 'Vargas',   'ci' => '3344556', 'cel' => '78334455', 'roles' => ['Chofer', 'Jefe de Grupo', 'Propietario'], 'placa' => '2001-BBB', 'marca' => 'Toyota',     'modelo' => 'Ipsum'],
                    ['pn' => 'Gonzalo', 'pa' => 'Gutiérrez','ci' => '4455667', 'cel' => '79445566', 'roles' => ['Chofer', 'Inspector', 'Propietario'],     'placa' => '2002-BBB', 'marca' => 'Nissan',     'modelo' => 'X-Trail'],
                    ['pn' => 'Hugo',    'pa' => 'Torrez',   'ci' => '5566778', 'cel' => '70556677', 'roles' => ['Chofer', 'Tesorero', 'Propietario'],      'placa' => '2003-BBB', 'marca' => 'Suzuki',     'modelo' => 'Grand Vitara'],
                    ['pn' => 'Efraín',  'pa' => 'Mendoza',  'ci' => '6677889', 'cel' => '71667788', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '2004-BBB', 'marca' => 'Toyota',     'modelo' => 'Rav4'],
                    ['pn' => 'Oscar',   'pa' => 'Paz',      'ci' => '7788990', 'cel' => '72778899', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '2005-BBB', 'marca' => 'Honda',      'modelo' => 'CR-V'],
                    ['pn' => 'Jorge',   'pa' => 'Arce',     'ci' => '8899001', 'cel' => '73889900', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '2006-BBB', 'marca' => 'Hyundai',    'modelo' => 'Santa Fe'],
                    ['pn' => 'René',    'pa' => 'Soliz',    'ci' => '9900112', 'cel' => '74990011', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '2007-BBB', 'marca' => 'Toyota',     'modelo' => 'Caldina'],
                ],
            ],

            // ─── GRUPO C ───────────────────────────────────────────────
            [
                'grupo' => 'Grupo C',
                'list'  => [
                    ['pn' => 'Walter',  'pa' => 'Calle',    'ci' => '1010101', 'cel' => '75101010', 'roles' => ['Chofer', 'Jefe de Grupo', 'Propietario'], 'placa' => '3001-CCC', 'marca' => 'Toyota',     'modelo' => 'Ipsum'],
                    ['pn' => 'Sergio',  'pa' => 'Villca',   'ci' => '2020202', 'cel' => '76202020', 'roles' => ['Chofer', 'Inspector', 'Propietario'],     'placa' => '3002-CCC', 'marca' => 'Toyota',     'modelo' => 'Probox'],
                    ['pn' => 'Hernán',  'pa' => 'Lima',     'ci' => '3030303', 'cel' => '77303030', 'roles' => ['Chofer', 'Tesorero', 'Propietario'],      'placa' => '3003-CCC', 'marca' => 'Hyundai',    'modelo' => 'Tucson'],
                    ['pn' => 'Rubén',   'pa' => 'Claros',   'ci' => '4040404', 'cel' => '78404040', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '3004-CCC', 'marca' => 'Nissan',     'modelo' => 'X-Trail'],
                    ['pn' => 'Iván',    'pa' => 'Choque',   'ci' => '5050505', 'cel' => '79505050', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '3005-CCC', 'marca' => 'Suzuki',     'modelo' => 'Grand Vitara'],
                    ['pn' => 'Edgar',   'pa' => 'Chávez',   'ci' => '6060606', 'cel' => '70606060', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '3006-CCC', 'marca' => 'Mitsubishi', 'modelo' => 'Outlander'],
                    ['pn' => 'Víctor',  'pa' => 'Ríos',     'ci' => '7070707', 'cel' => '71707070', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '3007-CCC', 'marca' => 'Toyota',     'modelo' => 'Rav4'],
                ],
            ],

            // ─── GRUPO D ───────────────────────────────────────────────
            [
                'grupo' => 'Grupo D',
                'list'  => [
                    ['pn' => 'Félix',   'pa' => 'Mamani',   'ci' => '8080808', 'cel' => '72808080', 'roles' => ['Chofer', 'Jefe de Grupo', 'Propietario'], 'placa' => '4001-DDD', 'marca' => 'Toyota',     'modelo' => 'Ipsum'],
                    ['pn' => 'David',   'pa' => 'Gutiérrez','ci' => '9012345', 'cel' => '76789012', 'roles' => ['Chofer', 'Inspector', 'Propietario'],     'placa' => '4002-DDD', 'marca' => 'Hyundai',    'modelo' => 'Tucson'],
                    ['pn' => 'Ramiro',  'pa' => 'Suárez',   'ci' => '9090909', 'cel' => '73909090', 'roles' => ['Chofer', 'Tesorero', 'Propietario'],      'placa' => '4003-DDD', 'marca' => 'Nissan',     'modelo' => 'X-Trail'],
                    ['pn' => 'Guillermo','pa' =>'Paredes',  'ci' => '1212121', 'cel' => '74121212', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '4004-DDD', 'marca' => 'Suzuki',     'modelo' => 'Grand Vitara'],
                    ['pn' => 'Alonso',  'pa' => 'Terán',    'ci' => '2323232', 'cel' => '75232323', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '4005-DDD', 'marca' => 'Honda',      'modelo' => 'CR-V'],
                    ['pn' => 'Marcelo', 'pa' => 'Veizaga',  'ci' => '3434343', 'cel' => '76343434', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '4006-DDD', 'marca' => 'Toyota',     'modelo' => 'Caldina'],
                    ['pn' => 'Jaime',   'pa' => 'Montero',  'ci' => '4545454', 'cel' => '77454545', 'roles' => ['Chofer', 'Propietario'],                  'placa' => '4007-DDD', 'marca' => 'Hyundai',    'modelo' => 'Santa Fe'],
                ],
            ],
        ];

        foreach ($todosChoferes as $grupoBlock) {
            $grupoModel = $grupos[$grupoBlock['grupo']];

            foreach ($grupoBlock['list'] as $item) {
                // 1. Persona
                $persona = Persona::firstOrCreate(
                    ['ci' => $item['ci']],
                    [
                        'primer_nombre'   => $item['pn'],
                        'primer_apellido' => $item['pa'],
                        'celular'         => $item['cel'],
                        'estado'          => true,
                        'usuarioA'        => 'system',
                        'fechaA'          => now(),
                    ]
                );

                // 2. Chofer
                $chofer = Chofer::firstOrCreate(
                    ['persona_id' => $persona->id],
                    [
                        'fecha_ingreso' => '2021-01-15',
                        'estado'        => true,
                        'usuarioA'      => 'system',
                        'fechaA'        => now(),
                    ]
                );

                // 3. Usuario
                $username = strtolower(str_replace(' ', '', $item['pn'])) . '.' . strtolower(str_replace(' ', '', $item['pa']));
                $usuario = Usuario::firstOrCreate(
                    ['username' => $username],
                    [
                        'persona_id' => $persona->id,
                        'password'   => Hash::make('password'),
                        'estado'     => true,
                        'usuarioA'   => 'system',
                        'fechaA'     => now(),
                    ]
                );

                // 4. Asignar Roles
                $rolIds = [];
                foreach ($item['roles'] as $rNombre) {
                    if (isset($roles[$rNombre])) {
                        $rolIds[$roles[$rNombre]->id] = ['estado' => true, 'usuarioA' => 'system', 'fechaA' => now()];
                    }
                }
                $usuario->roles()->syncWithoutDetaching($rolIds);

                // 5. Gestión de Vagoneta y Propietario (Cada chofer es propietario de su propia vagoneta)
                if (isset($item['placa'])) {
                    $propietario = Propietario::firstOrCreate(
                        ['persona_id' => $persona->id],
                        [
                            'fecha_registro' => '2021-01-15',
                            'estado'         => true,
                            'usuarioA'       => 'system',
                            'fechaA'         => now(),
                        ]
                    );

                    $auto = Auto::firstOrCreate(
                        ['placa' => $item['placa']],
                        [
                            'propietario_id' => $propietario->id,
                            'marca'          => $item['marca'],
                            'modelo'         => $item['modelo'],
                            'gestion'        => 2020,
                            'estado'         => true,
                            'usuarioA'       => 'system',
                            'fechaA'         => now(),
                        ]
                    );

                    // Asignar el chofer a su vagoneta en su grupo
                    ChoferAuto::firstOrCreate(
                        ['auto_id' => $auto->id, 'chofer_id' => $chofer->id],
                        [
                            'grupo_id' => $grupoModel->id,
                            'estado'   => true,
                            'usuarioA' => 'system',
                            'fechaA'   => now(),
                        ]
                    );
                }
            }
        }
    }
}
