<?php

namespace App\Http\Controllers\Api\books;

use App\Http\Controllers\Controller;
use App\Http\Requests\books\StoreBookUserRatingRequest;
use App\Http\Resources\books\BookUserRatingResource;
use App\Models\EditionUserRating;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookUserRatingController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $ratings = EditionUserRating::with([
            'user:id,name',
            'book:id,title'
        ])
            ->select(['id', 'user_id', 'book_id', 'rating', 'created_at'])
            ->orderByDesc('id')
            ->paginate($perPage);
        return $this->paginatedResponse($ratings, BookUserRatingResource::class, 'Berhasil menampilkan data');
    }
    public function store(StoreBookUserRatingRequest $request): JsonResponse
    {
        $rating = EditionUserRating::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'book_id' => $request->book_id,
            ],
            [
                'rating' => $request->rating,
            ]
        );
        return $this->successResponse(
            new BookUserRatingResource($rating),
            'Data berhasil disimpan.',
            201
        );

    }
    public function show($id): JsonResponse
    {
        $rating = EditionUserRating::with(['user', 'book'])->findOrFail($id);
        return $this->successResponse(
            new BookUserRatingResource($rating),
            'Detail rating berhasil ditampilkan.'
        );

    }
    public function destroy($id): JsonResponse
    {
        EditionUserRating::findOrFail($id)->delete();

        return $this->successResponse(
            null,
            'Rating berhasil dihapus.',
            200
        );
    }
}
