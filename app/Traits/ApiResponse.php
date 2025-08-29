<?php
namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

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
        if ($data instanceof AnonymousResourceCollection && method_exists($data->resource, 'perPage')) {
            $items = $data->items();
            $response['result'] = $items;
            $currentPage = $data->currentPage();
            $lastPage = $data->lastPage();

            $response['pagination'] = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'next_page' => ($currentPage < $lastPage) ? ($currentPage + 1) : null,
                'prev_page' => ($currentPage > 1) ? ($currentPage - 1) : null,
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ];
        } elseif ($data !== null) {
            $response['result'] = $data;
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
            // 'system_message' => $system_message,
        ];

        if ($system_message !== null && $system_message == 'Route [login] not defined.') {
            $response['message'] .= ', ' . $system_message;
        }

        if($system_message !== null && $system_message !== '') {
            $response['system_message'] = $system_message;
        }
        return response()->json($response, $code);
    }
}
