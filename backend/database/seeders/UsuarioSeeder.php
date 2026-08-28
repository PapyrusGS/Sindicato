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
        $paradasData = ['Obelisco', 'Villa Fátima', 'Parada 3', 'Parada 4'];

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
        // 6. 7 CHOFERES POR CADA GRUPO (28 CHOFERES EN TOTAL)
        // ═══════════════════════════════════════════════════════════════
        $todosChoferes = [
            // ─── GRUPO A ───────────────────────────────────────────────
            [
                'grupo' => 'Grupo A',
                'list'  => [
                    ['pn' => 'Roberto', 'pa' => 'Choque',   'ci' => '7854901', 'cel' => '74567890', 'roles' => ['Chofer', 'Jefe de Grupo'], 'placa' => '1001-AAA', 'marca' => 'Toyota',   'modelo' => 'Hiace'],
                    ['pn' => 'Miguel',  'pa' => 'Flores',   'ci' => '6743890', 'cel' => '73456789', 'roles' => ['Chofer', 'Inspector'],     'placa' => '1002-AAA', 'marca' => 'Hyundai',  'modelo' => 'H1'],
                    ['pn' => 'Pedro',   'pa' => 'Condori',  'ci' => '5632789', 'cel' => '72345678', 'roles' => ['Chofer', 'Tesorero'],      'placa' => '1003-AAA', 'marca' => 'Nissan',   'modelo' => 'Urvan'],
                    ['pn' => 'Juan',    'pa' => 'Mamani',   'ci' => '4521678', 'cel' => '71234567', 'roles' => ['Chofer'],                  'placa' => '1004-AAA', 'marca' => 'Toyota',   'modelo' => 'Coaster'],
                    ['pn' => 'Carlos',  'pa' => 'Ticona',   'ci' => '8965012', 'cel' => '75678901', 'roles' => ['Chofer'],                  'placa' => '1005-AAA', 'marca' => 'Mercedes', 'modelo' => 'Sprinter'],
                    ['pn' => 'Mario',   'pa' => 'Quispe',   'ci' => '1122334', 'cel' => '76112233', 'roles' => ['Chofer'],                  'placa' => '1006-AAA', 'marca' => 'Toyota',   'modelo' => 'Hiace'],
                    ['pn' => 'Luis',    'pa' => 'Apaza',    'ci' => '2233445', 'cel' => '77223344', 'roles' => ['Chofer'],                  'placa' => '1007-AAA', 'marca' => 'Hyundai',  'modelo' => 'H1'],
                ],
            ],

            // ─── GRUPO B (Propietario Efraín Mendoza con 2 choferes) ────
            [
                'grupo' => 'Grupo B',
                'list'  => [
                    ['pn' => 'Raúl',    'pa' => 'Vargas',   'ci' => '3344556', 'cel' => '78334455', 'roles' => ['Chofer', 'Jefe de Grupo'], 'placa' => '2001-BBB', 'marca' => 'Toyota',   'modelo' => 'Coaster'],
                    ['pn' => 'Gonzalo', 'pa' => 'Gutiérrez','ci' => '4455667', 'cel' => '79445566', 'roles' => ['Chofer', 'Inspector'],     'placa' => '2002-BBB', 'marca' => 'Nissan',   'modelo' => 'Urvan'],
                    ['pn' => 'Hugo',    'pa' => 'Torrez',   'ci' => '5566778', 'cel' => '70556677', 'roles' => ['Chofer', 'Tesorero'],      'placa' => '2003-BBB', 'marca' => 'Mercedes', 'modelo' => 'Sprinter'],
                    // Efraín Mendoza es Propietario y Chofer del auto 2004-BBB
                    ['pn' => 'Efraín',  'pa' => 'Mendoza',  'ci' => '6677889', 'cel' => '71667788', 'roles' => ['Chofer', 'Propietario'],   'placa' => '2004-BBB', 'marca' => 'Toyota',   'modelo' => 'Hiace', 'es_propietario_duo' => true],
                    // Oscar Paz es el Segundo Chofer que conduce el vehículo 2004-BBB de Efraín
                    ['pn' => 'Oscar',   'pa' => 'Paz',      'ci' => '7788990', 'cel' => '72778899', 'roles' => ['Chofer'],                  'auto_compartido' => '2004-BBB'],
                    ['pn' => 'Jorge',   'pa' => 'Arce',     'ci' => '8899001', 'cel' => '73889900', 'roles' => ['Chofer'],                  'placa' => '2005-BBB', 'marca' => 'Hyundai',  'modelo' => 'H1'],
                    ['pn' => 'René',    'pa' => 'Soliz',    'ci' => '9900112', 'cel' => '74990011', 'roles' => ['Chofer'],                  'placa' => '2006-BBB', 'marca' => 'Toyota',   'modelo' => 'Coaster'],
                ],
            ],

            // ─── GRUPO C ───────────────────────────────────────────────
            [
                'grupo' => 'Grupo C',
                'list'  => [
                    ['pn' => 'Walter',  'pa' => 'Calle',    'ci' => '1010101', 'cel' => '75101010', 'roles' => ['Chofer', 'Jefe de Grupo'], 'placa' => '3001-CCC', 'marca' => 'Nissan',   'modelo' => 'Urvan'],
                    ['pn' => 'Sergio',  'pa' => 'Villca',   'ci' => '2020202', 'cel' => '76202020', 'roles' => ['Chofer', 'Inspector'],     'placa' => '3002-CCC', 'marca' => 'Toyota',   'modelo' => 'Hiace'],
                    ['pn' => 'Hernán',  'pa' => 'Lima',     'ci' => '3030303', 'cel' => '77303030', 'roles' => ['Chofer', 'Tesorero'],      'placa' => '3003-CCC', 'marca' => 'Hyundai',  'modelo' => 'H1'],
                    ['pn' => 'Rubén',   'pa' => 'Claros',   'ci' => '4040404', 'cel' => '78404040', 'roles' => ['Chofer'],                  'placa' => '3004-CCC', 'marca' => 'Mercedes', 'modelo' => 'Sprinter'],
                    ['pn' => 'Iván',    'pa' => 'Choque',   'ci' => '5050505', 'cel' => '79505050', 'roles' => ['Chofer'],                  'placa' => '3005-CCC', 'marca' => 'Toyota',   'modelo' => 'Coaster'],
                    ['pn' => 'Edgar',   'pa' => 'Chávez',   'ci' => '6060606', 'cel' => '70606060', 'roles' => ['Chofer'],                  'placa' => '3006-CCC', 'marca' => 'Nissan',   'modelo' => 'Urvan'],
                    ['pn' => 'Víctor',  'pa' => 'Ríos',     'ci' => '7070707', 'cel' => '71707070', 'roles' => ['Chofer'],                  'placa' => '3007-CCC', 'marca' => 'Toyota',   'modelo' => 'Hiace'],
                ],
            ],

            // ─── GRUPO D (Propietario Guillermo Paredes con 2 choferes) 
            [
                'grupo' => 'Grupo D',
                'list'  => [
                    ['pn' => 'Félix',   'pa' => 'Mamani',   'ci' => '8080808', 'cel' => '72808080', 'roles' => ['Chofer', 'Jefe de Grupo'], 'placa' => '4001-DDD', 'marca' => 'Toyota',   'modelo' => 'Coaster'],
                    ['pn' => 'David',   'pa' => 'Gutiérrez','ci' => '9012345', 'cel' => '76789012', 'roles' => ['Chofer', 'Inspector'],     'placa' => '4002-DDD', 'marca' => 'Hyundai',  'modelo' => 'H1'],
                    ['pn' => 'Ramiro',  'pa' => 'Suárez',   'ci' => '9090909', 'cel' => '73909090', 'roles' => ['Chofer', 'Tesorero'],      'placa' => '4003-DDD', 'marca' => 'Nissan',   'modelo' => 'Urvan'],
                    // Guillermo Paredes es Propietario y Chofer del auto 4004-DDD
                    ['pn' => 'Guillermo','pa' =>'Paredes',  'ci' => '1212121', 'cel' => '74121212', 'roles' => ['Chofer', 'Propietario'],   'placa' => '4004-DDD', 'marca' => 'Mercedes', 'modelo' => 'Sprinter', 'es_propietario_duo' => true],
                    // Alonso Terán es el Segundo Chofer que comparte el auto 4004-DDD de Guillermo
                    ['pn' => 'Alonso',  'pa' => 'Terán',    'ci' => '2323232', 'cel' => '75232323', 'roles' => ['Chofer'],                  'auto_compartido' => '4004-DDD'],
                    ['pn' => 'Marcelo', 'pa' => 'Veizaga',  'ci' => '3434343', 'cel' => '76343434', 'roles' => ['Chofer'],                  'placa' => '4005-DDD', 'marca' => 'Toyota',   'modelo' => 'Hiace'],
                    ['pn' => 'Jaime',   'pa' => 'Montero',  'ci' => '4545454', 'cel' => '77454545', 'roles' => ['Chofer'],                  'placa' => '4006-DDD', 'marca' => 'Hyundai',  'modelo' => 'H1'],
                ],
            ],
        ];

        // Mapa de autos creados por placa para vincular segundos choferes
        $autosPorPlaca = [];

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

                // 5. Gestión de Vehículo y Propietario
                if (isset($item['placa'])) {
                    // Este afiliado es propietario de un nuevo auto
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

                    $autosPorPlaca[$item['placa']] = $auto;

                    // Asignar el chofer a este auto en su grupo
                    ChoferAuto::firstOrCreate(
                        ['auto_id' => $auto->id, 'chofer_id' => $chofer->id],
                        [
                            'grupo_id' => $grupoModel->id,
                            'estado'   => true,
                            'usuarioA' => 'system',
                            'fechaA'   => now(),
                        ]
                    );
                } else if (isset($item['auto_compartido'])) {
                    // Es un segundo chofer asignado a un auto existente del propietario
                    $autoExistente = $autosPorPlaca[$item['auto_compartido']] ?? null;

                    if ($autoExistente) {
                        ChoferAuto::firstOrCreate(
                            ['auto_id' => $autoExistente->id, 'chofer_id' => $chofer->id],
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
}
