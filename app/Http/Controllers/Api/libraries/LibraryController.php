<?php

namespace App\Http\Controllers\Api\libraries;

use App\Http\Controllers\Controller;
use App\Http\Requests\libraries\LibraryFilterRequest;
use App\Http\Resources\libraries\LibraryCollectionResource;
use App\Http\Resources\libraries\LibraryResource;
use App\Models\Library;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
class LibraryController extends Controller
{
    use ApiResponse;
    function index(LibraryFilterRequest $request)
    {
        $perPage = $request->get('per_page', 15);
        $order = $request->get('order', 'asc');
        $sort = $request->get('sort', 'id');

        $lat = $request->get('lat', '0');
        $lon = $request->get('lon', '0');

        $data = Library::select([
            'id',
            'name',
            'address',
            'lon',
            'lat',
            DB::raw("(
            6371 * acos(
                cos(radians($lat)) *
                cos(radians(lat)) *
                cos(radians(lon) - radians($lon)) +
                sin(radians($lat)) *
                sin(radians(lat))
            )
        ) AS distance")
        ])
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($perPage);
        return $this->paginatedResponse($data, LibraryCollectionResource::class, 'Berhasil menampilkan data.');
    }
    function show($id)
    {
        $library = Library::firstOrFail($id);
        return $this->successResponse(new LibraryResource($library), 'Berhasil menampilkan data.');
    }
}
