<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SimpleOptionResource;
use App\Models\BookTitle;
use App\Models\Edition;
use App\Services\EditionTitleListService;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookTitleController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(Request $request, EditionTitleListService $service)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $q = $request->input('search');
        $titles = $service->list($q);
        return $this->setResponse('Berhasil menampilkan daftar judul buku.', SimpleOptionResource::collection($titles));
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $this->authorize('manage', Edition::class);

        $raw = trim($request->input('title'));
        $slug = Str::appSlug($raw);
        if ($existing = BookTitle::where('slug', $slug)->first()) {
            return $this->setErrorResponse("Judul buku $raw sudah ada.", 409, $existing);
        }
        DB::beginTransaction();
        try {
            BookTitle::create([
                'title' => $raw,
                'slug' => $slug,
            ]);
            DB::commit();
            return $this->setResponse('Judul buku berhasil disimpan', null, 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyimpan judul buku', 500, $e->getMessage());
        }
    }
    public function update(Request $request, BookTitle $bookTitle)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $this->authorize('manage', Edition::class);

        $raw = trim($request->input('title'));
        $slug = Str::appSlug($raw);

        $conflict = BookTitle::where('slug', $slug)
            ->where('id', '!=', $bookTitle->id)
            ->first();

        if ($conflict) {
            return $this->setErrorResponse("Judul buku {$raw} sudah ada.", 409, [
                'exists' => $conflict,
            ]);
        }

        if ($bookTitle->title === $raw && $bookTitle->slug === $slug) {
            return $this->setResponse('Tidak ada perubahan.', null, 200);
        }

        DB::beginTransaction();
        try {
            $bookTitle->update([
                'title' => $raw,
                'slug' => $slug,
            ]);

            DB::commit();
            return $this->setResponse('Judul buku berhasil diperbarui', null, 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal memperbarui judul buku', 500, $e->getMessage());
        }
    }
    public function destroy(Request $request, BookTitle $bookTitle)
    {
        $this->authorize('manage', Edition::class);
        $hasActive = $bookTitle->editions()->exists();
        if ($hasActive) {
            return $this->setErrorResponse(
                'Judul tidak dapat dihapus karena masih ada edisi aktif.',
                409,
                ['active_editions' => $hasActive]
            );
        }

        DB::beginTransaction();
        try {
            // if ($request->boolean('force')) {
            //     $anyEdition = $bookTitle->editions()->withTrashed()->exists();
            //     if ($anyEdition) {
            //         DB::rollBack();
            //         return $this->setErrorResponse(
            //             'Tidak dapat menghapus permanen: masih ada edisi (termasuk yang terhapus).',
            //             409
            //         );
            //     }
            //     $bookTitle->forceDelete();
            //     DB::commit();
            //     return $this->setResponse('Judul dihapus permanen.', null, 200);
            // }
            $bookTitle->delete();
            DB::commit();
            return $this->setResponse('Judul berhasil dihapus.', null, 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus judul.', 500, $e->getMessage());
        }
    }
}
