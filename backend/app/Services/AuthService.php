<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * @var UsuarioRepositoryInterface
     */
    protected UsuarioRepositoryInterface $usuarioRepository;

    /**
     * Crear una nueva instancia del servicio.
     *
     * @param  UsuarioRepositoryInterface  $usuarioRepository
     */
    public function __construct(UsuarioRepositoryInterface $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Iniciar sesión y generar token de acceso.
     *
     * @param  array  $credentials
     * @return array
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $usuario = $this->usuarioRepository->findByUsername($credentials['username']);

        if (!$usuario || !Hash::check($credentials['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$usuario->estado) {
            throw ValidationException::withMessages([
                'username' => ['Su cuenta ha sido desactivada. Contacte al administrador.'],
            ]);
        }

        // Revocar tokens anteriores
        $usuario->tokens()->delete();

        // Crear nuevo token
        $token = $usuario->createToken('auth-token')->plainTextToken;

        return [
            'usuario' => $usuario->load('persona', 'roles'),
            'token'   => $token,
        ];
    }

    /**
     * Registrar un nuevo usuario.
     * Requiere que la persona ya exista en el sistema.
     *
     * @param  array  $data
     * @return array
     */
    public function register(array $data): array
    {
        $usuario = $this->usuarioRepository->create([
            'persona_id' => $data['persona_id'],
            'username'   => $data['username'],
            'password'   => Hash::make($data['password']),
            'estado'     => true,
        ]);

        $token = $usuario->createToken('auth-token')->plainTextToken;

        return [
            'usuario' => $usuario->load('persona', 'roles'),
            'token'   => $token,
        ];
    }

    /**
     * Cerrar sesión (revocar token actual).
     *
     * @param  Usuario  $usuario
     * @return void
     */
    public function logout(Usuario $usuario): void
    {
        $usuario->currentAccessToken()->delete();
    }
}
