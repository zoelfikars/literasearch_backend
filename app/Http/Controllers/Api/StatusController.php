<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusListRequest;
use App\Http\Resources\SimpleOptionResource;
use App\Models\Status;
use App\Traits\ApiResponse;

class StatusController extends Controller
{
    use ApiResponse;
    public function list(StatusListRequest $request)
    {
        $query = Status::select(['id', 'description as name'])->where('type', $request->input('type'))->get();
        $data = SimpleOptionResource::collection($query);
        return $this->setResponse('Berhasil menampilkan list status.', $data, 200);
    }
}
