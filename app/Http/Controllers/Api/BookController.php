<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookFilterRequest;
use App\Http\Resources\BookResource;
use App\Models\Edition;
use App\Models\Pivots\EditionRating;
use App\Services\BookListService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Storage;

class BookController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function list(BookFilterRequest $request, BookListService $service)
    {
        $libraries = $service->list($request, null);
        $data = BookResource::collection($libraries);
        return $this->setResponse('Berhasil menampilkan buku.', $data);
    }
    public function serveCover(Request $request, $id)
    {
        $book = Edition::find($id);
        if (!$book) {
            return $this->setErrorResponse('Data buku tidak ditemukan.', 404);
        }

        $path = $book->cover;
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
    public function rate(Request $request, Edition $edition)
    {
        $user = $request->user('sanctum');
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);
        $rating = $request->input('rating');
        DB::beginTransaction();
        try {
            $edition->raters()->syncWithoutDetaching([
                $user->id => ['rating' => (int) $rating],
            ]);
            DB::commit();
            return $this->setResponse('Berhasil melakukan rating pada buku', null, 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyimpan rating.', 500, $e->getMessage());
        }
    }
}
