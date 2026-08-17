<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UsuarioResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    /**
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * Crear una nueva instancia del controller.
     *
     * @param  AuthService  $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Iniciar sesión.
     *
     * POST /api/auth/login
     *
     * @param  LoginRequest  $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->sendResponse([
                'usuario' => new UsuarioResource($result['usuario']),
                'token'   => $result['token'],
            ], 'Inicio de sesión exitoso');
        } catch (ValidationException $e) {
            return $this->sendError(
                'Error de autenticación',
                $e->errors(),
                401
            );
        }
    }

    /**
     * Registrar un nuevo usuario.
     *
     * POST /api/auth/register
     *
     * @param  RegisterRequest  $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->sendResponse([
            'usuario' => new UsuarioResource($result['usuario']),
            'token'   => $result['token'],
        ], 'Registro exitoso', 201);
    }

    /**
     * Cerrar sesión.
     *
     * POST /api/auth/logout
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->sendResponse(null, 'Sesión cerrada correctamente');
    }

    /**
     * Obtener el usuario autenticado.
     *
     * GET /api/auth/me
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user()->load('persona', 'roles');

        return $this->sendResponse(
            new UsuarioResource($usuario),
            'Usuario autenticado'
        );
    }
}
