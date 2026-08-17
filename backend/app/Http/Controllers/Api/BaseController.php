<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Enviar respuesta exitosa estandarizada.
     *
     * @param  mixed   $data
     * @param  string  $message
     * @param  int     $code
     * @return JsonResponse
     */
    protected function sendResponse($data = null, string $message = 'Operación exitosa', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Enviar respuesta de error estandarizada.
     *
     * @param  string  $error
     * @param  array   $errorMessages
     * @param  int     $code
     * @return JsonResponse
     */
    protected function sendError(string $error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
