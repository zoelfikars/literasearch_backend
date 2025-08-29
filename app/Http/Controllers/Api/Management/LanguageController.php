<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Services\LanguageListService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(Request $request, LanguageListService $service)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $q = $request->input('search');
        $languages = $service->list($q);
        return $this->setResponse('Berhasil menampilkan daftar bahasa.', LanguageResource::collection($languages));
    }
}
