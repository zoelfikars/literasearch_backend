<?php
namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
trait ApiResponse
{
    protected function setResponse(
        string $message = 'OK',
        mixed $data = null,
        int $code = 200,
        string $status = 'success',
    ): JsonResponse {
        $status = $code >= 200 && $code < 300 ? 'success' : 'error';
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        if ($data instanceof LengthAwarePaginator) {
            $items = $data->items();
            $response['data'] = $items;
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ];
        } elseif ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $code);
    }

    public static function setErrorResponse(
        $message = 'Terjadi kesalahan pada server',
        int $code = 400,
        $system_message = null,
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($system_message !== null) {
            $response['system_message'] = $system_message;
        }
        return response()->json($response, $code);
    }
}
