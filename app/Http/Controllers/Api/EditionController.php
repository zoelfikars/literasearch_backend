<?php
namespace App\Http\Controllers\Api;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\LibraryFilterRequest;
use App\Http\Requests\RateRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\LibraryResource;
use App\Models\EditionComment;
use App\Models\EditionReadPosition;
use App\Models\Library;
use App\Services\LibraryListService;
use Auth;
use DB;
use Schema;
use Storage;
use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditionFilterRequest;
use App\Http\Requests\EditionStoreRequest;
use App\Http\Requests\EditionUpdateRequest;
use App\Http\Resources\EditionDetailResource;
use App\Http\Resources\EditionResource;
use App\Models\Edition;
use App\Services\EditionListService;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class EditionController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(EditionFilterRequest $request, EditionListService $service, ?Library $library = null)
    {
        $libraries = $service->list($request, $library);
        $data = EditionResource::collection($libraries);
        return $this->setResponse('Berhasil menampilkan buku.', $data);
    }
    public function serveCover(Request $request, Edition $book)
    {
        $path = $book->cover;
        if (empty($path) || !Storage::disk('private')->exists($path)) {
            return $this->setErrorResponse('Gambar sampul buku tidak ditemukan', 404);
        }
        $disk = Storage::disk('private');
        $fullPath = $disk->path($path);
        $lastModified = $disk->lastModified($path);
        $size = $disk->size($path);
        $mime = $disk->mimeType($path) ?? 'image/jpeg';
        $etagCacheKey = 'edition:' . $book->getKey() . ':cover_etag:' . md5($path . '|' . $lastModified . '|' . $size);
        $etag = Cache::remember($etagCacheKey, now()->addDays(7), function () use ($fullPath, $path, $lastModified, $size) {
            return @sha1_file($fullPath) ?: sha1($path . '|' . $lastModified . '|' . $size);
        });
        $response = new BinaryFileResponse($fullPath);
        $response->setPublic();
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->setEtag($etag);
        $response->setLastModified((new \DateTimeImmutable())->setTimestamp($lastModified));
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->setContentDisposition('inline', basename($path));
        if ($response->isNotModified($request)) {
            return $response;
        }
        return $response;
    }
    public function serveRead(Request $request, Edition $book)
    {
        $this->authorize('read', $book);
        $path = $book->file_path;
        if (!$path || !Storage::disk('private')->exists($path)) {
            return $this->setErrorResponse('Buku digital tidak ditemukan', 404);
        }
        $disk = Storage::disk('private');
        $fullPath = $disk->path($path);
        $lastModified = $disk->lastModified($path);
        $size = $disk->size($path);
        $mime = $disk->mimeType($path) ?? 'application/octet-stream';
        $etagCacheKey = 'edition:' . $book->getKey() . ':file_etag:' . md5($path . '|' . $lastModified . '|' . $size);
        $etag = Cache::remember($etagCacheKey, now()->addDay(), function () use ($fullPath, $path, $lastModified, $size) {
            return @sha1_file($fullPath) ?: sha1($path . '|' . $lastModified . '|' . $size);
        });
        $response = new BinaryFileResponse($fullPath);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        $response->setEtag($etag);
        $response->setLastModified((new \DateTimeImmutable())->setTimestamp($lastModified));
        $response->headers->set('Vary', 'Authorization, Cookie');
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition('inline', basename($path));
        if ($response->isNotModified($request)) {
            return $response;
        }
        return $response;
    }
    public function rate(RateRequest $request, Edition $book)
    {
        $user = $request->user('sanctum');
        DB::beginTransaction();
        try {
            $book->raters()->syncWithoutDetaching([
                $user->id => ['rating' => $request->input('rating')],
            ]);
            DB::commit();
            return $this->setResponse('Berhasil melakukan rating pada buku', null, 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyimpan rating.', 500, $e->getMessage());
        }
    }
    public function libraries(LibraryFilterRequest $request, Edition $book, LibraryListService $service)
    {
        $user = $request->user('sanctum');
        $libraries = $service->list($request, $user, $book);
        $data = LibraryResource::collection($libraries);
        return $this->setResponse("Berhasil mengambil data perpustakaan yang memiliki buku", $data, 200);
    }
    public function store(EditionStoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $this->authorize('manage', Edition::class);
            $data = $request->validated();
            $data['book_title_id'] = $data['title_id'];
            unset($data['title_id']);
            $edition = Edition::create([
                'isbn_10' => $data['isbn_10'] ?? null,
                'isbn_13' => $data['isbn_13'] ?? null,
                'edition_number' => $data['edition_number'],
                'publication_year' => $data['publication_year'],
                'cover' => $data['cover'] ?? null,
                'file_path' => $data['file_path'] ?? null,
                'pages' => $data['pages'],
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? null,
                'book_title_id' => $data['book_title_id'],
                'publisher_id' => $data['publisher_id'],
                'language_id' => $data['language_id'],
            ]);
            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $coverPath = $file->storeAs('editions/cover/', $edition->id . '.' . $file->getClientOriginalExtension(), 'private');
                $edition->cover = $coverPath;
            }
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->storeAs('editions/files/', $edition->id . '.' . $file->getClientOriginalExtension(), 'private');
                $edition->file_path = $filePath;
            }
            if (!empty($data['subject_ids'])) {
                $edition->subjects()->sync($data['subject_ids']);
            }
            if (!empty($data['contributors'])) {
                foreach ($data['contributors'] as $c) {
                    $exists = $edition->contributors()
                        ->where('authors.id', $c['author_id'])
                        ->wherePivot('role_id', $c['role_id'])
                        ->exists();
                    if (!$exists) {
                        $edition->contributors()->attach($c['author_id'], [
                            'role_id' => $c['role_id'],
                        ]);
                    }
                }
            }
            $edition->save();
            DB::commit();
            return $this->setResponse("Berhasil menambahkan data buku", null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setResponse("Gagal menambahkan data buku", $e->getMessage(), 500);
        }
    }
    public function show(Request $request, Edition $book)
    {
        $q = Edition::query()
            ->with([
                'title:id,title as name',
                'publisher:id,name',
                'language:id,english_name as name',
                'contributors',
                'subjects:id,name',
                'ratingRecords',
                'libraries',
            ])
            ->withRatingsAgg()
            ->where('editions.id', $book->id);
        $userId = Auth::guard('sanctum')->id();
        if ($userId) {
            $q->leftJoin('edition_read_positions as erp', function ($j) use ($userId) {
                $j->on('erp.edition_id', '=', 'editions.id')
                    ->where('erp.user_id', '=', $userId);
            });
            $q->addSelect([
                'editions.*',
                DB::raw("
                ROUND(
                    LEAST(
                        100,
                        CASE
                            WHEN erp.locator_type = 'page'
                                 AND erp.page IS NOT NULL
                                 AND editions.pages > 0
                            THEN (erp.page / editions.pages) * 100
                            WHEN erp.locator_type = 'cfi'
                                 AND erp.progress_percent IS NOT NULL
                            THEN erp.progress_percent
                            ELSE 0
                        END
                    ), 2
                ) as user_read_progress
            "),
                DB::raw('erp.locator_type as user_locator_type'),
                DB::raw('erp.page as user_read_page'),
                DB::raw('erp.progress_percent as user_read_percent'),
                DB::raw('erp.cfi as user_read_cfi'),
                DB::raw('erp.last_opened_at as user_last_opened_at'),
            ]);
        }
        if ($userId) {
            $q->leftJoin('edition_wishlists as ewl', function ($j) use ($userId) {
                $j->on('ewl.edition_id', '=', 'editions.id')
                    ->where('ewl.user_id', '=', $userId);
            });
            $q->addSelect('editions.*');
            $q->addSelect([
                DB::raw('CASE WHEN ewl.user_id IS NULL THEN 0 ELSE 1 END as is_wishlisted'),
            ]);
        } else {
            $q->addSelect('editions.*');
            $q->addSelect([
                DB::raw('NULL as is_wishlisted'),
                DB::raw('NULL as user_read_progress'),
                DB::raw('NULL as user_locator_type'),
                DB::raw('NULL as user_read_page'),
                DB::raw('NULL as user_read_percent'),
                DB::raw('NULL as user_read_cfi'),
                DB::raw('NULL as user_last_opened_at'),
            ]);
        }
        $book = $q->firstOrFail();
        return $this->setResponse("Berhasil mengambil data buku", new EditionDetailResource($book), 200);
    }
    public function update(EditionUpdateRequest $request, Edition $book)
    {
        try {
            DB::beginTransaction();
            $this->authorize('manage', $book);
            $data = $request->validated();
            if (isset($data['title_id'])) {
                $data['book_title_id'] = $data['title_id'];
                unset($data['title_id']);
            }
            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $coverPath = $file->storeAs('editions/cover/', $book->id . '.' . $file->getClientOriginalExtension(), 'private');
                $data['cover'] = $coverPath;
            }
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->storeAs('editions/files/', $book->id . '.' . $file->getClientOriginalExtension(), 'private');
                $data['file_path'] = $filePath;
            }
            $book->update([
                'isbn_10' => $data['isbn_10'] ?? $book->isbn_10,
                'isbn_13' => $data['isbn_13'] ?? $book->isbn_13,
                'edition_number' => $data['edition_number'] ?? $book->edition_number,
                'publication_year' => $data['publication_year'] ?? $book->publication_year,
                'cover' => $data['cover'] ?? $book->cover,
                'file_path' => $data['file_path'] ?? $book->file_path,
                'pages' => $data['pages'] ?? $book->pages,
                'subtitle' => $data['subtitle'] ?? $book->subtitle,
                'description' => $data['description'] ?? $book->description,
                'book_title_id' => $data['book_title_id'] ?? $book->book_title_id,
                'publisher_id' => $data['publisher_id'] ?? $book->publisher_id,
                'language_id' => $data['language_id'] ?? $book->language_id,
            ]);
            if (isset($data['subject_ids'])) {
                $book->subjects()->sync($data['subject_ids']);
            }
            if (isset($data['contributors'])) {
                $book->contributors()->detach();
                foreach ($data['contributors'] as $c) {
                    $book->contributors()->attach($c['author_id'], [
                        'role_id' => $c['role_id'],
                    ]);
                }
            }
            DB::commit();
            return $this->setResponse("Berhasil memperbarui data buku", null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setResponse("Gagal memperbarui data buku", $e->getMessage(), 500);
        }
    }
    public function wishlist(Request $request, Edition $book)
    {
        $user = $request->user('sanctum');
        $exists = $user->wishlist()->where('edition_id', $book->id)->exists();
        if ($exists) {
            $user->wishlist()->detach($book->id);
            return $this->setResponse('Buku dihapus dari daftar keinginan.', null, 200);
        } else {
            $user->wishlist()->attach($book->id);
            return $this->setResponse('Buku ditambahkan ke daftar keinginan.', null, 200);
        }
    }
    public function storePosition(Request $request, Edition $book)
    {
        $EPSILON_DROP = 0.5; // toleransi penurunan (0.5%)
        $NEAR_DONE = 99.0; // dianggap selesai
        $NEAR_START_PCT = 2.0;  // dianggap awal (persen)
        $NEAR_START_PAGE = 2;    // halaman 1–2 dianggap awal

        $userId = Auth::guard('sanctum')->id() ?? $request->user()?->id;
        if (!$userId) {
            return $this->setResponse('Unauthorized: butuh login untuk menyimpan posisi.', null, 401);
        }

        $data = $request->validate([
            'locator_type' => 'required|in:page,cfi',
            'page' => 'nullable|required_if:locator_type,page|integer|min:1',
            'cfi' => 'nullable|required_if:locator_type,cfi|string',
            // (opsional) izinkan klien mengirim percent saat CFI agar akurat:
            'progress_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $attrs = [
            'locator_type' => $data['locator_type'],
            'page' => $data['locator_type'] === 'page' ? ($data['page'] ?? null) : null,
            'cfi' => $data['locator_type'] === 'cfi' ? ($data['cfi'] ?? null) : null,
            'progress_percent' => null, // dihitung/diambil di bawah
            'last_opened_at' => now(),
        ];

        // Sinkronisasi progress_percent untuk mode page
        if ($attrs['locator_type'] === 'page' && $attrs['page'] && $book->pages > 0) {
            $attrs['progress_percent'] = round(min(100, ($attrs['page'] / $book->pages) * 100), 2);
        }

        // Untuk CFI, jika klien mengirim progress_percent, pakai itu; jika tidak, biarkan null (jangan paksa 0)
        if ($attrs['locator_type'] === 'cfi' && array_key_exists('progress_percent', $data)) {
            $attrs['progress_percent'] = (float) $data['progress_percent'];
        }

        // Helper hitung persen
        $calcPercent = function (?string $type, ?int $page, ?float $percent) use ($book): float {
            if ($type === 'page') {
                return ($page && $book->pages > 0) ? min(100, ($page / $book->pages) * 100) : 0.0;
            }
            if ($type === 'cfi') {
                // kalau percent tidak tersedia, jangan asumsikan 0; kembalikan NaN sebagai “unknown”
                return is_null($percent) ? NAN : max(0, min(100, (float) $percent));
            }
            return 0.0;
        };

        $existing = EditionReadPosition::where('user_id', $userId)
            ->where('edition_id', $book->id)
            ->first();

        $old = $existing
            ? $calcPercent($existing->locator_type, $existing->page, $existing->progress_percent)
            : 0.0;

        $new = $calcPercent($attrs['locator_type'], $attrs['page'], $attrs['progress_percent']);

        // Deteksi restart:
        // - PAGE: lama ≈ selesai dan page baru kecil
        // - CFI:  lama ≈ selesai dan percent baru eksplisit kecil (hanya jika dikirim)
        $looksLikeRestart =
            ($old >= $NEAR_DONE) && (
                ($attrs['locator_type'] === 'page' && (int) ($attrs['page'] ?? 0) <= $NEAR_START_PAGE)
                ||
                ($attrs['locator_type'] === 'cfi'
                    && array_key_exists('progress_percent', $data) // hanya pertimbangkan jika dikirim
                    && is_finite($new) && $new <= $NEAR_START_PCT)
            );

        if ($existing) {
            if ($looksLikeRestart) {
                // (Opsional) pastikan kolom read_cycle sudah ada sebelum dipakai
                if (Schema::hasColumn('edition_read_positions', 'read_cycle')) {
                    $existing->increment('read_cycle');
                }
                $existing->update($attrs + ['last_opened_at' => now()]);
                return $this->setResponse('Posisi tersimpan (mulai ulang terdeteksi).', null, 200);
            }

            // Blokir penurunan nyata: hanya jika kita punya $new yang valid
            if (is_finite($new) && $new + $EPSILON_DROP < $old) {
                $existing->update(['last_opened_at' => now()]);
                return $this->setResponse('Posisi tidak diturunkan.', null, 200);
            }

            $existing->update($attrs);
        } else {
            EditionReadPosition::create([
                'user_id' => $userId,
                'edition_id' => $book->id,
            ] + $attrs);
        }

        return $this->setResponse('Posisi tersimpan.', null, 200);
    }
    public function commentList(Request $request, Edition $book)
    {
        $perPage = $request->get('per_page', 15);
        $comments = $book->comments()->with('user')->paginate($perPage);
        $comments = CommentResource::collection($comments);
        return $this->setResponse('Berhasil menampilkan daftar komentar.', $comments);
    }
    public function commentStore(CommentRequest $request, Edition $book)
    {
        $this->authorize('store', EditionComment::class);
        $user = $request->user('sanctum');
        DB::beginTransaction();
        try {
            $book->comments()->create([
                'user_id' => $user->id,
                'text' => $request->input('text'),
            ]);
            DB::commit();
            return $this->setResponse('Berhasil menambahkan komentar.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menambahkan komentar.', 500, $e->getMessage());
        }
    }
    public function commentDelete(EditionComment $id)
    {
        $this->authorize('delete', $id);
        DB::beginTransaction();
        try {
            $id->delete();
            DB::commit();
            return $this->setResponse('Berhasil menghapus komentar.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus komentar.', 500, $e->getMessage());
        }
    }
}
