<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Http\Resources\SimpleOptionResource;
use App\Models\Publisher;
use App\Services\PublisherListService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Str;

class PublisherController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(Request $request, PublisherListService $service)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $q = $request->input('search');
        $publishers = $service->list($q);
        return $this->setResponse('Berhasil menampilkan daftar penerbit.', PublisherResource::collection($publishers));
    }
    public function store(StorePublisherRequest $request)
    {
        $this->authorize('store', Publisher::class);

        $raw_name = trim($request->input('name'));
        $slug_name = Str::appSlug($raw_name);
        $raw_city = trim($request->input('city'));
        $slug_city = Str::appSlug($raw_city);
        $raw_address = trim($request->input('address'));
        if ($existing = Publisher::where('slug_name', $slug_name)->where('slug_city', $slug_city)->first()) {
            return $this->setErrorResponse("Penerbit $raw_name di kota/kabupaten $raw_city sudah ada.", 409, $existing);
        }
        DB::beginTransaction();
        try {
            Publisher::create([
                'name' => $raw_name,
                'slug_name' => $slug_name,
                'city' => $raw_city,
                'slug_city' => $slug_city,
                'address' => $raw_address,
            ]);
            DB::commit();
            return $this->setResponse('Penerbit berhasil disimpan', null, 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyimpan penerbit', 500, $e->getMessage());
        }
    }
    public function update(StorePublisherRequest $request, Publisher $publisher)
    {
        $this->authorize('update', $publisher);

        $raw_name = trim($request->input('name'));
        $slug_name = Str::appSlug($raw_name);
        $raw_city = trim($request->input('city'));
        $slug_city = Str::appSlug($raw_city);
        $raw_address = trim($request->input('address'));

        $conflict = Publisher::where('slug_name', $slug_name)
            ->where('slug_city', $slug_city)
            ->where('id', '!=', $publisher->id)
            ->first();

        if ($conflict) {
            return $this->setErrorResponse("Penerbit $raw_name di kota/kabupaten $raw_city sudah ada.", 409, [
                'exists' => $conflict,
            ]);
        }

        if (
            $publisher->name === $raw_name &&
            $publisher->slug_name === $slug_name &&
            $publisher->city === $raw_city &&
            $publisher->slug_city === $slug_city &&
            $publisher->address === $raw_address
        ) {
            return $this->setResponse('Tidak ada perubahan.', null, 200);
        }

        DB::beginTransaction();
        try {
            $publisher->update([
                'name' => $raw_name,
                'slug_name' => $slug_name,
                'city' => $raw_city,
                'slug_city' => $slug_city,
                'address' => $raw_address,
            ]);

            DB::commit();
            return $this->setResponse('Penerbit berhasil diperbarui', null, 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal memperbarui penerbit', 500, $e->getMessage());
        }
    }
    public function destroy(Request $request, Publisher $publisher)
    {
        $this->authorize('delete', $publisher);
        $hasActive = $publisher->editions()->exists();
        if ($hasActive) {
            return $this->setErrorResponse(
                'Penerbit tidak dapat dihapus karena masih ada edisi aktif.',
                409,
                ['active_editions' => $hasActive]
            );
        }

        DB::beginTransaction();
        try {
            if ($request->boolean('force')) {
                $anyEdition = $publisher->editions()->withTrashed()->exists();
                if ($anyEdition) {
                    DB::rollBack();
                    return $this->setErrorResponse(
                        'Tidak dapat menghapus permanen: masih ada edisi (termasuk yang terhapus).',
                        409
                    );
                }
                $publisher->forceDelete();
                DB::commit();
                return $this->setResponse('Penerbit dihapus permanen.', null, 204);
            }
            $publisher->delete();
            DB::commit();
            return $this->setResponse('Penerbit berhasil dihapus.', null, 204);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus penerbit.', 500, $e->getMessage());
        }
    }
}
