<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\LibraryApplicationExtendRequest;
use App\Http\Requests\LibraryApplicationStoreRequest;
use App\Http\Requests\LibraryEditRequest;
use App\Http\Requests\LibraryFilterRequest;
use App\Http\Requests\RateRequest;
use App\Http\Requests\RejectionRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\LibraryApplicationResource;
use App\Http\Resources\LibraryResource;
use App\Http\Resources\LibraryDetailResource;
use App\Models\Library;
use App\Models\LibraryApplication;
use App\Models\LibraryComment;
use App\Models\Status;
use App\Services\LibraryListService;
use App\Traits\ApiResponse;
use Auth;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class LibraryController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    function list(LibraryFilterRequest $request, LibraryListService $service)
    {
        $user = $request->user('sanctum');
        $libraries = $service->list($request, $user);
        $data = LibraryResource::collection($libraries);
        return $this->setResponse('Berhasil menampilkan perpustakaan.', $data);
    }
    public function show(Library $library)
    {
        $userId = Auth::guard('sanctum')->id();
        $q = Library::query()
            ->select('libraries.*')
            ->with([
                'latestApprovedByExpiration',
                'members',
                'librarians',
                'ratingRecords',
            ])
            ->withRatingsAgg()
            ->withCounts();
        if ($userId) {
            $q->withExists([
                'librarians as is_librarian_exists' => fn($qq) => $qq->where('users.id', $userId),
                'members as is_member_exists' => fn($qq) => $qq->where('users.id', $userId),
            ]);
        }
        $library = $q->findOrFail($library->id);
        return $this->setResponse(
            'Berhasil menampilkan detail perpustakaan',
            new LibraryDetailResource($library)
        );
    }
    public function serveCover(Request $request, Library $library)
    {
        $path = $library->image_path;
        if (empty($path) || !Storage::disk('private')->exists($path)) {
            return $this->setErrorResponse('Gambar sampul buku tidak ditemukan', 404);
        }
        $disk = Storage::disk('private');
        $fullPath = $disk->path($path);
        $lastModified = $disk->lastModified($path);
        $size = $disk->size($path);
        $mime = $disk->mimeType($path) ?? 'image/jpeg';
        $etagCacheKey = 'edition:' . $library->getKey() . ':cover_etag:' . md5($path . '|' . $lastModified . '|' . $size);
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
    public function rate(RateRequest $request, Library $library)
    {
        $user = $request->user('sanctum');
        DB::beginTransaction();
        try {
            $user->ratedLibraries()->syncWithoutDetaching([
                $library->id => ['rating' => $request->input('rating')],
            ]);
            DB::commit();
            return $this->setResponse('Berhasil memberikan penilaian perpustakaan.', null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal memberikan penilaian perpustakaan.', 500, $e->getMessage());
        }
    }
    public function commentList(Library $library)
    {
        if (!$library->is_active) {
            return $this->setErrorResponse('Perpustakaan tidak aktif.', 403);
        }
        $perPage = request()->get('per_page', 15);
        $comments = $library->comments()->with('user')->paginate($perPage);
        $comments = CommentResource::collection($comments);
        return $this->setResponse('Berhasil menampilkan daftar komentar.', $comments);
    }
    public function commentStore(CommentRequest $request, Library $library)
    {
        if (!$library->is_active) {
            return $this->setErrorResponse('Perpustakaan tidak aktif.', 403);
        }
        $this->authorize('store', LibraryComment::class);
        $user = $request->user('sanctum');
        DB::beginTransaction();
        try {
            $library->comments()->create([
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
    public function commentDelete(LibraryComment $id)
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
    public function update(LibraryEditRequest $request, Library $library)
    {
        $this->authorize('edit', $library);
        DB::beginTransaction();
        $imagePath = null;
        try {
            $data = [
                "name" => $request->input("name", $library->name),
                "address" => $request->input("address", $library->address),
                "description" => $request->input("description", $library->description),
                "phone_number" => $request->input("phone", $library->phone_number),
                "latitude" => $request->input("latitude", $library->latitude),
                "longitude" => $request->input("longitude", $library->longitude),
                "is_recruiting" => $request->input("recruitment", $library->recruitment),
            ];
            $file = $request->file("image");
            if ($file) {
                $fileName = $library->id . "." . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs('libraries/cover', $fileName, 'private');
                $data['image_path'] = $imagePath;
            }
            $library->update($data);
            $data = $library->load('latestApprovedByExpiration');
            DB::commit();
            return $this->setResponse("Data perpustakaan berhasil diperbarui", new LibraryDetailResource($data), 200);
        } catch (Exception $e) {
            DB::rollBack();
            if ($imagePath) {
                Storage::disk('private')->delete($imagePath);
            }
            return $this->setErrorResponse("Gagal memperbarui data perpustakaan", 500, $e->getMessage());
        }
    }
    public function store(LibraryApplicationStoreRequest $request)
    {
        $validatedData = $request->validated();
        $documentPath = null;
        $imagePath = null;
        DB::beginTransaction();
        try {
            $user = $request->user();
            $statusId = Status::where('type', 'library_application')->where('name', 'pending')->value('id');
            $library = Library::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'address' => $validatedData['address'],
                'phone_number' => $validatedData['phone'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'owner_id' => $user->id,
            ]);
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imagePath = $file->storeAs(
                    'libraries/cover/',
                    $library->id . '(' . now()->format('YmdHis') . ').' . $file->getClientOriginalExtension(),
                    'private'
                );
            }
            $library->image_path = $imagePath;
            $library->save();
            $libraryId = $library->id;
            $application = LibraryApplication::create([
                'library_id' => $libraryId,
                'expiration_date' => $validatedData['expiration_date'],
                'user_id' => $user->id,
                'status_id' => $statusId,
            ]);
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $documentPath = $file->storeAs('libraries/applications', $application->id . '(' . now()->format('YmdHis') . ').' . $file->getClientOriginalExtension(), 'private');
            }
            $application->document_path = $documentPath;
            $application->save();
            DB::commit();
            return $this->setResponse('Pengajuan perpustakaan berhasil dikirim', null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($documentPath) {
                Storage::disk('private')->delete($documentPath);
            }
            if ($imagePath) {
                Storage::disk('private')->delete($imagePath);
            }
            return $this->setErrorResponse('Pengajuan perpustakaan gagal', 500, $e->getMessage());
        }
    }
    public function showApplication(LibraryApplication $application)
    {
        $this->authorize('view', [$application->user->identity]);
        $application->load(['library', 'status', 'user.identity', 'inspector']);
        return $this->setResponse('Detail pengajuan berhasil diambil', new LibraryApplicationResource($application), 200);
    }
    public function approve(LibraryApplication $application)
    {
        $this->authorize('approve', $application);
        $authUser = Auth::user();
        $allowedStatuses = ['pending'];
        if (!in_array($application->status->name, $allowedStatuses)) {
            return $this->setErrorResponse('Pengajuan sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'library_application')->where('name', 'approved')->first();
            $application->update([
                'status_id' => $status->id,
                'inspector_id' => $authUser->id,
                'inspected_at' => now(),
            ]);
            $library = $application->library;
            if ($library) {
                $library->load('applications');
                $applicationLength = $library->applications->count();
                if (!($applicationLength > 1)) {
                    $application->load('user');
                    $user = $application->user;
                    $user->managedLibraries()->syncWithoutDetaching([
                        $library->id => ['is_active' => true]
                    ]);
                }
                $library->is_active = true;
                $library->save();
            }
            $application->user->assignRole('Pustakawan');
            DB::commit();
            return $this->setResponse('Pengajuan berhasil disetujui', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menyetujui pengajuan', 500, $e->getMessage());
        }
    }
    public function reject(LibraryApplication $application, RejectionRequest $request)
    {
        $this->authorize('reject', $application);
        $authUser = $request->user();
        if ($application->status->name !== 'pending') {
            return $this->setErrorResponse('Pengajuan sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'library_application')->where('name', 'rejected')->first();
            $application->update([
                'status_id' => $status->id,
                'inspector_id' => $authUser->id,
                'inspected_at' => now(),
                'rejection_reason' => $request->input('reason'),
            ]);
            $library = $application->library;
            if ($library) {
                $library->load('applications');
                $applicationLength = $library->applications->count();
                if ($applicationLength == 1) {
                    $library->delete();
                }
            }
            DB::commit();
            return $this->setResponse('Pengajuan berhasil ditolak', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menolak pengajuan', 500, $e->getMessage());
        }
    }
    public function serveLibraryApplicationDocument(LibraryApplication $application, Request $request)
    {
        $this->authorize('viewDocument', $application);
        $path = $application->document_path;
        if (!Storage::disk('private')->exists($path)) {
            return $this->setErrorResponse('Dokumen tidak ditemukan', 404);
        }
        $fullPath = Storage::disk('private')->path($path);
        $lastModified = Storage::disk('private')->lastModified($path);
        $size = Storage::disk('private')->size($path);
        $etag = sha1($path . '|' . $lastModified . '|' . $size);
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=600, immutable')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        }
        if ($ifModifiedSince = $request->headers->get('If-Modified-Since')) {
            if (strtotime($ifModifiedSince) >= $lastModified) {
                return response('', 304)
                    ->header('ETag', $etag)
                    ->header('Cache-Control', 'public, max-age=600, immutable')
                    ->header('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
            }
        }
        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=600, immutable',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        ]);
    }
    public function extend(LibraryApplicationExtendRequest $request, Library $library)
    {
        $this->authorize('extend', [LibraryApplication::class, $library]);
        $validatedData = $request->validated();
        $documentPath = null;
        if ($request->hasFile("document")) {
            $file = $request->file("document");
        }
        DB::beginTransaction();
        try {
            $statusId = Status::where("type", "library_application")->where("name", "pending")->value("id");
            $library_extend = $library->latestApprovedByExpiration;
            if (!empty($library_extend)) {
                $expired = Carbon::parse($library_extend->expiration_date)->isPast();
                if (!$expired) {
                    return $this->setErrorResponse(
                        "Pengajuan hanya bisa jika dokumen sudah kadaluarsa.",
                        422
                    );
                }
            }
            $library_pending = $library->pendingApplications()->first();
            if (!empty($library_pending)) {
                return $this->setErrorResponse(
                    "Anda sudah mengajukan perpanjangan.",
                    422
                );
            }
            $application = LibraryApplication::create([
                "library_id" => $library->id,
                "document_path" => $documentPath,
                "expiration_date" => $validatedData["expiration_date"],
                "user_id" => $request->user()->id,
                "status_id" => $statusId,
            ]);
            $documentPath = $file->storeAs('libraries/applications', $application->id . '(' . now()->format('YmdHis') . ').' . $file->getClientOriginalExtension(), 'private');
            $application->document_path = $documentPath;
            $application->save();
            DB::commit();
            return $this->setResponse("Pengajuan perpanjangan perpustakaan berhasil dikirim", null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($documentPath) {
                Storage::disk("private")->delete($documentPath);
            }
            return $this->setErrorResponse("Pengajuan perpanjangan perpustakaan gagal", 500, $e->getMessage());
        }
    }
}
