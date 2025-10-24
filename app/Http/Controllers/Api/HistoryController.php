<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\HistoryListRequest;
use App\Http\Resources\HistoryResource;
use App\Services\HistoryService;
use App\Traits\ApiResponse;
class HistoryController extends Controller
{
    use ApiResponse;
    public function list(HistoryListRequest $request, HistoryService $service)
    {
        $user = $request->user('sanctum');
        $paginator = $service->list($request, $user);
        $data = HistoryResource::collection($paginator);
        return $this->setResponse('Berhasil menampilkan riwayat.', $data);
    }
}
