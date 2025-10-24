<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SimpleOptionResource;
use App\Models\Edition;
use App\Models\Subject;
use App\Services\SubjectListService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Str;

class EditionSubjectController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(Request $request, SubjectListService $service)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $q = $request->input('search');
        $subjects = $service->list($q);
        return $this->setResponse('Berhasil menampilkan daftar tema buku.', SimpleOptionResource::collection($subjects));
    }
    public function store(Request $request)
    {
        $this->authorize('manage', Edition::class);
        $request->validate([
            'subject' => 'required|string|max:255',
        ]);
        $raw = trim($request->input('subject'));
        $slug = Str::appSlug($raw);
        if ($existing = Subject::where('slug', $slug)->first()) {
            return $this->setErrorResponse("Tema Buku $raw sudah ada.", 409, $existing);
        }
        DB::beginTransaction();
        try {
            Subject::create([
                'name' => $raw,
                'slug' => $slug,
            ]);
            DB::commit();
            return $this->setResponse('Tema Buku berhasil disimpan', null, 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyimpan tema buku', 500, $e->getMessage());
        }
    }
    public function update(Request $request, Subject $subject)
    {
        $this->authorize('manage', Edition::class);
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
        ]);
        $raw = trim($request->input('subject'));
        $slug = Str::appSlug($raw);

        $conflict = Subject::where('slug', $slug)
            ->where('id', '!=', $subject->id)
            ->first();

        if ($conflict) {
            return $this->setErrorResponse("Tema Buku {$raw} sudah ada.", 409, [
                'exists' => $conflict,
            ]);
        }

        if ($subject->subject === $raw && $subject->slug === $slug) {
            return $this->setResponse('Tidak ada perubahan.', null, 200);
        }

        DB::beginTransaction();
        try {
            $subject->update([
                'name' => $raw,
                'slug' => $slug,
            ]);

            DB::commit();
            return $this->setResponse('Tema Buku berhasil diperbarui', null, 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal memperbarui tema buku', 500, $e->getMessage());
        }
    }
    public function destroy(Request $request, Subject $subject)
    {
        $this->authorize('manage', Edition::class);
        $hasActive = $subject->editions()->exists();
        if ($hasActive) {
            return $this->setErrorResponse(
                'Tema buku tidak dapat dihapus karena masih ada edisi aktif.',
                409,
                ['active_editions' => $hasActive]
            );
        }

        DB::beginTransaction();
        try {
            // if ($request->boolean('force')) {
            //     $anyEdition = $subject->editions()->withTrashed()->exists();
            //     if ($anyEdition) {
            //         DB::rollBack();
            //         return $this->setErrorResponse(
            //             'Tidak dapat menghapus permanen: masih ada edisi (termasuk yang terhapus).',
            //             409
            //         );
            //     }
            //     $subject->forceDelete();
            //     DB::commit();
            //     return $this->setResponse('Tema buku dihapus permanen.', null, 200);
            // }
            $subject->delete();
            DB::commit();
            return $this->setResponse('Tema buku berhasil dihapus.', null, 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus tema buku.', 500, $e->getMessage());
        }
    }
}
