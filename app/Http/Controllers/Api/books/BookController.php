<?php

namespace App\Http\Controllers\Api\books;

use App\Http\Controllers\Controller;
use App\Http\Requests\books\BookFilterRequest;
use App\Http\Requests\books\BookLibraryRequest;
use App\Http\Requests\books\BookTrendingRequest;
use App\Http\Resources\books\BookCollectionResource;
use App\Http\Resources\books\BookResource;
use App\Http\Resources\books\BookTrendingCollectionResource;
use App\Http\Resources\libraries\LibraryCollectionResource;
use App\Models\Book;
use App\Models\Library;
use App\Models\Subject;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    use ApiResponse;

    public function index(BookFilterRequest $request)
    {
        dd('test');
        $perPage = $request->get('per_page', 15);
        $order = $request->get('order', 'asc');
        $sort = $request->get('sort', 'id');

        $data = Book::select(['id', 'title', 'author', 'cover_url'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('author', 'like', '%' . $request->search . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($perPage);

        DB::beginTransaction();
        try {
            foreach ($data as $book) {
                if ($book) {
                    $isbn = $book->isbn;
                    if ($book->cover_url === null) {
                        $book->cover_url = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
                    }
                    $book->save();
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Gagal mengambil data buku dari Open Library.', 500);
        }
        return $this->paginatedResponse($data, BookCollectionResource::class, 'Berhasil menampilkan data buku.');
    }
    public function trending(BookTrendingRequest $request)
    {
        $perPage = $request->get('per_page', 15);
        $order = $request->get('order', 'desc');

        $range = $request->get('range', 'daily');
        $books = Book::withCount([
            'ratings as rating_count' => function ($query) use ($range) {
                $query->when(
                    $range === 'daily',
                    fn($q) =>
                    $q->whereDate('created_at', now()->toDateString())
                )->when(
                        $range === 'weekly',
                        fn($q) =>
                        $q->whereBetween('created_at', [now()->subDays(7), now()])
                    )->when(
                        $range === 'monthly',
                        fn($q) =>
                        $q->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                    )->when(
                        $range === 'yearly',
                        fn($q) =>
                        $q->whereYear('created_at', now()->year)
                    );
            }
        ])->orderBy('rating_count', $order)
            ->paginate($perPage);

        DB::beginTransaction();
        try {
            foreach ($books as $book) {
                if ($book->cover_url === null && $book->isbn) {
                    $book->cover_url = "https://covers.openlibrary.org/b/isbn/{$book->isbn}-L.jpg";
                    $book->save();
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Gagal memproses cover buku.', 500);
        }
        return $this->paginatedResponse($books, BookTrendingCollectionResource::class, 'Berhasil menampilkan buku trending.');
    }
    public function show($id)
    {
        $book = Book::find($id);
        if (!is_null($book)) {
            DB::beginTransaction();
            try {
                $responseWork = null;
                if ($book) {
                    $isbn = $book->isbn;
                    if (
                        $book->cover_url === null &&
                        $book->description === null &&
                        $book->subjects()->count() === 0
                    ) {
                        $book->cover_url = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
                        $responseWork = file_get_contents("https://openlibrary.org/isbn/{$isbn}.json");
                        $workData = json_decode($responseWork, true);
                        $workComponents = isset($workData['works']) ? $workData['works'] : [];
                        $firstWorkComponent = is_array($workComponents) && !empty($workComponents) ? reset($workComponents) : null;
                        $subjectWork = isset($workData['subjects']) ? $workData['subjects'] : [];
                        if (is_array($subjectWork) && !empty($subjectWork)) {
                            foreach ($subjectWork as $subjectName) {
                                if (!$book->subjects()->where('name', $subjectName)->exists()) {
                                    $subject = Subject::firstOrCreate(['name' => $subjectName]);
                                    $book->subjects()->attach($subject);
                                }
                            }
                        }

                        $key = $firstWorkComponent['key'] ?? null;
                        $response = file_get_contents("https://openlibrary.org{$key}.json");
                        $responseData = json_decode($response, true);
                        $subjectResponse = isset($responseData['subjects']) ? $responseData['subjects'] : [];
                        if (is_array($subjectResponse) && !empty($subjectResponse)) {
                            foreach ($subjectResponse as $subjectName) {
                                if (!$book->subjects()->where('name', $subjectName)->exists()) {
                                    $subject = Subject::firstOrCreate(['name' => $subjectName]);
                                    $book->subjects()->attach($subject);
                                }
                            }
                        }
                        $descriptionResponse = isset($responseData['description']) ? $responseData['description'] : [];
                        if (is_array($descriptionResponse) && !empty($descriptionResponse)) {
                            $descriptionValue = null;

                            if (is_array($descriptionResponse) && isset($descriptionResponse['value'])) {
                                $descriptionValue = $descriptionResponse['value'];
                            } elseif (is_string($descriptionResponse)) {
                                $descriptionValue = $descriptionResponse;
                            } else {
                                $descriptionValue = null;
                            }

                            if ($descriptionValue) {
                                $descriptionValue = html_entity_decode(trim(preg_replace('/\s+/', ' ', $descriptionValue)));
                            }
                            $book->description = $descriptionValue;
                        }
                    }
                    $book->save();
                }
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->errorResponse('Gagal mengambil data buku dari Open Library.', 500);
            }
            return $this->successResponse(new BookResource($book), 'Berhasil menampilkan detail buku.');
        } else {
            return $this->successResponse(null, 'Buku tidak ditemukan.');
        }
    }
    public function libraries($id, BookLibraryRequest $request)
    {
        $book = Book::findOrFail($id);
        $lat = $request->get('lat', '0');
        $lon = $request->get('lon', '0');

        $libraries = $book->library()
            ->select([
                'libraries.id',
                'libraries.name',
                'libraries.address',
                'libraries.lat',
                'libraries.lon',
                DB::raw("(
                6371 * acos(
                cos(radians($lat)) *
                cos(radians(libraries.lat)) *
                cos(radians(libraries.lon) - radians($lon)) +
                sin(radians($lat)) *
                sin(radians(libraries.lat))
                )
            ) AS distance")
            ])
            ->orderBy('distance', 'asc')
            ->get();

        return $this->successResponse(
            LibraryCollectionResource::collection($libraries),
            'Berhasil menampilkan perpustakaan untuk buku ini.'
        );
    }
}
