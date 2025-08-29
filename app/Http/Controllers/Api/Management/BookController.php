<?php
namespace App\Http\Controllers\Api\Management;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Models\Edition;
use App\Traits\ApiResponse;
use DB;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
class BookController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function store(StoreBookRequest $request)
    {
        try {
            DB::beginTransaction();
            $this->authorize('store', Edition::class);
            $data = $request->validated();
            $data['book_title_id'] = $data['title_id'];
            unset($data['title_id']);
            $edition = Edition::create([
                'isbn_10' => $data['isbn_10'] ?? null,
                'isbn_13' => $data['isbn_13'] ?? null,
                'edition_number' => $data['edition_number'],
                'publication_date' => $data['publication_date'],
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
                $data['cover'] = $coverPath;
            }
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->storeAs('editions/files/', $edition->id . '.' . $file->getClientOriginalExtension(), 'private');
                $data['file_path'] = $filePath;
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
            DB::commit();
            return $this->setResponse("Berhasil menambahkan data buku", null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setResponse("Gagal menambahkan data buku", $e->getMessage(), 500);
        }
    }
}
