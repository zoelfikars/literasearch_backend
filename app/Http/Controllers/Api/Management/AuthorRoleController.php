<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Http\Resources\SimpleOptionResource;
use App\Services\AuthorRoleListService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AuthorRoleController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(Request $request, AuthorRoleListService $service)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $q = $request->input('search');
        $authors = $service->list($q);
        return $this->setResponse('Berhasil menampilkan daftar peran kontributor.', SimpleOptionResource::collection($authors));
    }
}
