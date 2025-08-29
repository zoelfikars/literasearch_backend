<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use App\Services\AuthorListService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Str;

class AuthorController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(Request $request, AuthorListService $service)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $q = $request->input('search');
        $authors = $service->list($q);
        return $this->setResponse('Berhasil menampilkan daftar pengarang.', AuthorResource::collection($authors));
    }
    public function store(StoreAuthorRequest $request)
    {
        $this->authorize('store', Author::class);

        $name = trim($request->input('author_name'));
        $slug = Str::appSlug($name);
        $dis = $request->input('disambiguator') ? trim($request->input('disambiguator')) : null;
        $existsAny = Author::where('slug', $slug)->where('disambiguator', $dis)->exists();
        if ($existsAny) {
            $message = "Sudah ada pengarang dengan nama $name, ";
            if ($dis) {
                $message .= "dengan disambiguator $dis, pastikan penulis tidak duplikat. ";
            } else {
                $message .= 'Tambahkan disambiguator untuk membedakan orangnya (mis. lahir 1975 Bandung).';
            }
            return $this->setErrorResponse($message, 409);
        }
        DB::beginTransaction();
        try {
            Author::create([
                'name' => $name,
                'slug' => $slug,
                'disambiguator' => $dis,
            ]);
            DB::commit();
            return $this->setResponse('Pengarang berhasil disimpan', null, 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyimpan pengarang', 500, $e->getMessage());
        }
    }
    public function update(StoreAuthorRequest $request, Author $author)
    {
        $this->authorize('update', $author);

        $name = trim($request->input('author_name'));
        $slug = Str::appSlug($name);
        $dis = $request->disambiguator ? trim($request->disambiguator) : null;

        $conflict = Author::where('name', $name)
            ->where('slug', $slug)
            ->where('disambiguator', $dis)
            ->where('id', '!=', $author->id)
            ->first();

        if ($conflict) {
            return $this->setErrorResponse("Pengarang $name dengan disambiguator $dis sudah ada.", 409, [
                'exists' => $conflict,
            ]);
        }

        if (
            $author->name === $name &&
            $author->slug === $slug &&
            $author->disambiguator === $dis
        ) {
            return $this->setResponse('Tidak ada perubahan.', null, 200);
        }

        DB::beginTransaction();
        try {
            $author->update([
                'name' => $name,
                'slug' => $slug,
                'disambiguator' => $dis,
            ]);

            DB::commit();
            return $this->setResponse('Pengarang berhasil diperbarui', null, 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal memperbarui pengarang', 500, $e->getMessage());
        }
    }
    public function destroy(Request $request, Author $author)
    {
        $this->authorize('delete', $author);
        $hasActive = $author->editions()->exists();
        if ($hasActive) {
            return $this->setErrorResponse(
                'Pengarang tidak dapat dihapus karena masih ada edisi aktif.',
                409,
                ['active_editions' => $hasActive]
            );
        }

        DB::beginTransaction();
        try {
            if ($request->boolean('force')) {
                $anyEdition = $author->editions()->withTrashed()->exists();
                if ($anyEdition) {
                    DB::rollBack();
                    return $this->setErrorResponse(
                        'Tidak dapat menghapus permanen: masih ada edisi (termasuk yang terhapus).',
                        409
                    );
                }
                $author->forceDelete();
                DB::commit();
                return $this->setResponse('Pengarang dihapus permanen.', null, 204);
            }
            $author->delete();
            DB::commit();
            return $this->setResponse('Pengarang berhasil dihapus.', null, 204);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus pengarang.', 500, $e->getMessage());
        }
    }
}
