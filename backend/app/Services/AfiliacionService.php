<?php

namespace App\Services;

use App\Models\Auto;
use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AfiliacionService
{
    /**
     * Obtener listado de afiliados con filtros.
     */
    public function listarAfiliados(array $filters = [])
    {
        $query = Persona::with([
            'usuario.roles',
            'chofer.autos.propietario.persona',
            'propietario.autos.choferes.persona',
        ]);

        if (isset($filters['estado'])) {
            $query->where('estado', filter_var($filters['estado'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('primer_nombre', 'like', "%{$search}%")
                  ->orWhere('primer_apellido', 'like', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate(15);
    }

    /**
     * Obtener datos auxiliares para los selectores del formulario de afiliación.
     */
    public function obtenerAuxiliares(): array
    {
        $roles = Rol::where('estado', true)->select('id', 'nombre', 'descripcion')->get();
        $grupos = DB::table('grupos')->where('estado', true)->select('id', 'nombre', 'descripcion')->get();

        $choferes = Chofer::with('persona')
            ->where('estado', true)
            ->get()
            ->map(fn($c) => [
                'id'              => $c->id,
                'nombre_completo' => $c->persona->nombre_completo,
                'ci'              => $c->persona->ci,
            ]);

        $autos = Auto::with('propietario.persona')
            ->where('estado', true)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'placa'       => $a->placa,
                'marca'       => $a->marca,
                'modelo'      => $a->modelo,
                'gestion'     => $a->gestion,
                'propietario' => $a->propietario?->persona?->nombre_completo,
            ]);

        return compact('roles', 'grupos', 'choferes', 'autos');
    }

    /**
     * Registrar un nuevo afiliado manejando los 3 casos transaccionalmente.
     */
    public function registrarAfiliado(array $data, string $usuarioAudit = 'system'): Persona
    {
        return DB::transaction(function () use ($data, $usuarioAudit) {
            $persona = Persona::create([
                'primer_nombre'    => $data['primer_nombre'],
                'segundo_nombre'   => $data['segundo_nombre'] ?? null,
                'primer_apellido'  => $data['primer_apellido'],
                'segundo_apellido' => $data['segundo_apellido'] ?? null,
                'ci'               => $data['ci'],
                'celular'          => $data['celular'] ?? null,
                'direccion'        => $data['direccion'] ?? null,
                'estado'           => true,
                'usuarioA'         => $usuarioAudit,
                'fechaA'           => now(),
            ]);

            $usuario = Usuario::create([
                'persona_id'    => $persona->id,
                'username'      => $data['username'],
                'password'      => Hash::make($data['password']),
                'estado'        => true,
                'usuarioA'      => $usuarioAudit,
                'fechaA'        => now(),
            ]);

            $rolesAsignados = $data['roles'] ?? [];
            $modalidad = $data['modalidad'];

            if ($modalidad === 'propietario_auto') {
                if (!in_array('Propietario', $rolesAsignados)) {
                    $rolesAsignados[] = 'Propietario';
                }

                $propietario = Propietario::create([
                    'persona_id'     => $persona->id,
                    'fecha_registro' => now(),
                    'estado'         => true,
                    'usuarioA'       => $usuarioAudit,
                    'fechaA'         => now(),
                ]);

                $auto = Auto::create([
                    'propietario_id' => $propietario->id,
                    'placa'          => strtoupper($data['auto_placa']),
                    'marca'          => $data['auto_marca'],
                    'modelo'         => $data['auto_modelo'],
                    'gestion'        => $data['auto_gestion'],
                    'estado'         => true,
                    'usuarioA'       => $usuarioAudit,
                    'fechaA'         => now(),
                ]);

                $esElChofer = filter_var($data['es_el_chofer'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($esElChofer) {
                    if (!in_array('Chofer', $rolesAsignados)) {
                        $rolesAsignados[] = 'Chofer';
                    }

                    $chofer = Chofer::create([
                        'persona_id'    => $persona->id,
                        'fecha_ingreso' => now(),
                        'estado'        => true,
                        'usuarioA'      => $usuarioAudit,
                        'fechaA'        => now(),
                    ]);

                    ChoferAuto::create([
                        'auto_id'       => $auto->id,
                        'chofer_id'     => $chofer->id,
                        'grupo_id'      => $data['grupo_id'],
                        'estado'        => true,
                        'usuarioA'      => $usuarioAudit,
                        'fechaA'        => now(),
                    ]);
                } else if (!empty($data['chofer_id_asignado'])) {
                    ChoferAuto::create([
                        'auto_id'       => $auto->id,
                        'chofer_id'     => $data['chofer_id_asignado'],
                        'grupo_id'      => $data['grupo_id'],
                        'estado'        => true,
                        'usuarioA'      => $usuarioAudit,
                        'fechaA'        => now(),
                    ]);
                }
            } else if ($modalidad === 'chofer_existente') {
                if (!in_array('Chofer', $rolesAsignados)) {
                    $rolesAsignados[] = 'Chofer';
                }

                $chofer = Chofer::create([
                    'persona_id'    => $persona->id,
                    'fecha_ingreso' => now(),
                    'estado'        => true,
                    'usuarioA'      => $usuarioAudit,
                    'fechaA'        => now(),
                ]);

                if (!empty($data['auto_id_existente'])) {
                    ChoferAuto::create([
                        'auto_id'       => $data['auto_id_existente'],
                        'chofer_id'     => $chofer->id,
                        'grupo_id'      => $data['grupo_id'],
                        'estado'        => true,
                        'usuarioA'      => $usuarioAudit,
                        'fechaA'        => now(),
                    ]);
                }
            }

            $rolIds = Rol::whereIn('nombre', $rolesAsignados)->pluck('id')->toArray();
            if (!empty($rolIds)) {
                $pivotData = array_fill_keys($rolIds, [
                    'estado'   => true,
                    'usuarioA' => $usuarioAudit,
                    'fechaA'   => now(),
                ]);
                $usuario->roles()->sync($pivotData);
            }

            return $persona->load([
                'usuario.roles',
                'chofer.autos',
                'propietario.autos',
            ]);
        });
    }

    /**
     * Actualizar datos de un afiliado existente con auditoría.
     */
    public function actualizarAfiliado(int $id, array $data, string $usuarioAudit = 'system'): Persona
    {
        return DB::transaction(function () use ($id, $data, $usuarioAudit) {
            $persona = Persona::with(['usuario', 'propietario.autos', 'chofer'])->findOrFail($id);

            // 1. Actualizar Persona
            $persona->update([
                'primer_nombre'    => $data['primer_nombre'],
                'segundo_nombre'   => $data['segundo_nombre'] ?? null,
                'primer_apellido'  => $data['primer_apellido'],
                'segundo_apellido' => $data['segundo_apellido'] ?? null,
                'ci'               => $data['ci'],
                'celular'          => $data['celular'] ?? null,
                'direccion'        => $data['direccion'] ?? null,
                'usuarioA'         => $usuarioAudit,
                'fechaA'           => now(),
            ]);

            // 2. Actualizar Usuario
            if ($persona->usuario) {
                $usuarioUpdate = [
                    'username' => $data['username'],
                    'usuarioA' => $usuarioAudit,
                    'fechaA'   => now(),
                ];
                if (!empty($data['password'])) {
                    $usuarioUpdate['password'] = Hash::make($data['password']);
                }
                $persona->usuario->update($usuarioUpdate);

                // Actualizar Roles
                if (isset($data['roles'])) {
                    $rolIds = Rol::whereIn('nombre', $data['roles'])->pluck('id')->toArray();
                    $pivotData = array_fill_keys($rolIds, [
                        'estado'   => true,
                        'usuarioA' => $usuarioAudit,
                        'fechaA'   => now(),
                    ]);
                    $persona->usuario->roles()->sync($pivotData);
                }
            }

            // 3. Actualizar Vehículo si es Propietario
            if ($persona->propietario && !empty($data['auto_id'])) {
                $auto = Auto::where('propietario_id', $persona->propietario->id)->find($data['auto_id']);
                if ($auto) {
                    $auto->update([
                        'placa'    => strtoupper($data['auto_placa'] ?? $auto->placa),
                        'marca'    => $data['auto_marca'] ?? $auto->marca,
                        'modelo'   => $data['auto_modelo'] ?? $auto->modelo,
                        'gestion'  => $data['auto_gestion'] ?? $auto->gestion,
                        'usuarioA' => $usuarioAudit,
                        'fechaA'   => now(),
                    ]);

                    if (!empty($data['grupo_id'])) {
                        ChoferAuto::where('auto_id', $auto->id)->update([
                            'grupo_id' => $data['grupo_id'],
                            'usuarioA' => $usuarioAudit,
                            'fechaA'   => now(),
                        ]);
                    }
                }
            }

            return $persona->load(['usuario.roles', 'chofer.autos', 'propietario.autos']);
        });
    }

    /**
     * Eliminación Lógica (alternar estado 1/0) con auditoría obligatoria.
     */
    public function cambiarEstado(int $id, bool $nuevoEstado, string $usuarioAudit = 'system'): Persona
    {
        return DB::transaction(function () use ($id, $nuevoEstado, $usuarioAudit) {
            $persona = Persona::with(['usuario', 'chofer', 'propietario.autos'])->findOrFail($id);

            // Persona
            $persona->estado = $nuevoEstado;
            $persona->usuarioA = $usuarioAudit;
            $persona->fechaA = now();
            $persona->save();

            // Usuario
            if ($persona->usuario) {
                $persona->usuario->estado = $nuevoEstado;
                $persona->usuario->usuarioA = $usuarioAudit;
                $persona->usuario->fechaA = now();
                $persona->usuario->save();
            }

            // Chofer
            if ($persona->chofer) {
                $persona->chofer->estado = $nuevoEstado;
                $persona->chofer->usuarioA = $usuarioAudit;
                $persona->chofer->fechaA = now();
                $persona->chofer->save();
            }

            // Propietario y sus autos
            if ($persona->propietario) {
                $persona->propietario->estado = $nuevoEstado;
                $persona->propietario->usuarioA = $usuarioAudit;
                $persona->propietario->fechaA = now();
                $persona->propietario->save();

                foreach ($persona->propietario->autos as $auto) {
                    $auto->estado = $nuevoEstado;
                    $auto->usuarioA = $usuarioAudit;
                    $auto->fechaA = now();
                    $auto->save();
                }
            }

            return $persona;
        });
    }
}
