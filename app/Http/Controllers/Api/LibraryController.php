<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\LibraryFilterRequest;
use App\Http\Requests\RateRequest;
use App\Http\Resources\BookCollectionResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\LibraryCollectionResource;
use App\Http\Resources\LibraryResource;
use App\Models\Library;
use App\Services\BookListService;
use App\Services\LibraryListService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    use ApiResponse;
    function list(LibraryFilterRequest $request, LibraryListService $service)
    {
        $user = $request->user('sanctum');
        $libraries = $service->list($request, $user);
        $data = LibraryCollectionResource::collection($libraries);
        return $this->setResponse('Berhasil menampilkan perpustakaan.', $data);
    }
    function show($id)
    {
        $library = Library::with('latestApprovedByExpiration')->find($id);
        if (!$library) {
            return $this->setErrorResponse('Perpustakaan tidak ditemukan.', 404);
        }
        $data = new LibraryResource($library);
        return $this->setResponse('Berhasil menampilkan detail perpustakaan', $data);
    }
    public function serveLibraryImage(Request $request, $id)
    {
        $library = Library::find($id);
        if (!$library) {
            return $this->setErrorResponse('Perpustakaan tidak ditemukan.', 404);
        }

        $path = $library->image_path;
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
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
    public function rate(RateRequest $request, $id)
    {
        $library = Library::find($id);
        $user = $request->user('sanctum');
        if (!$library) {
            return $this->setResponse('Perpustakaan tidak ditemukan.', null, 404);
        }
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
    public function commentList($id)
    {
        $library = Library::find($id);
        if (!$library) {
            return $this->setErrorResponse('Perpustakaan tidak ditemukan.', 404);
        }
        $perPage = request()->get('per_page', 15);
        $comments = $library->comments()->with('user')->paginate($perPage);
        $comments = CommentResource::collection($comments);
        return $this->setResponse('Berhasil menampilkan daftar komentar.', $comments);
    }
    public function commentStore($id, CommentRequest $request)
    {
        $library = Library::find($id);
        if (!$library) {
            return $this->setErrorResponse('Perpustakaan tidak ditemukan.', 404);
        }
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
    public function commentDelete($library, $id)
    {
        $library = Library::find($library);
        if (!$library) {
            return $this->setErrorResponse('Perpustakaan tidak ditemukan.', 404);
        }
        $comment = $library->comments()->find($id);
        if (!$comment) {
            return $this->setErrorResponse('Komentar tidak ditemukan.', 404);
        }
        DB::beginTransaction();
        try {
            $comment->delete();
            DB::commit();
            return $this->setResponse('Berhasil menghapus komentar.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus komentar.', 500, $e->getMessage());
        }
    }
    public function books(Request $request, BookListService $service, $id)
    {
        $library = Library::find($id);
        if (!$library) {
            return $this->setErrorResponse('Perpustakaan tidak ditemukan.', 404);
        }
        $books = $service->list($request, $library ?? null);
        $data = BookCollectionResource::collection($books);
        return $this->setResponse('Berhasil menampilkan buku.', $data);
    }
}
