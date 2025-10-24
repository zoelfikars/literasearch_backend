<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanFilterRequest;
use App\Http\Resources\LoanDetailResource;
use App\Http\Resources\LoanResource;
use App\Models\Edition;
use App\Models\Library;
use App\Models\Loan;
use App\Models\Status;
use App\Services\LoanListService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
class LoanController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function rent(Request $request, Library $library, Edition $book)
    {
        $this->authorize('rent', [Loan::class, $library]);
        $user = $request->user('sanctum');
        $request->validate([
            'due_at' => 'required|integer|min:1|max:4',
            'notes' => 'sometimes|string|max:255',
        ]);
        $existingPendingLoan = $user->loans()
            ->where('library_id', $library->id)
            ->where('edition_id', $book->id)
            ->loanStatus('pending')
            ->first();
        $existingNotReturnedLoan = $user->loans()
            ->where('library_id', $library->id)
            ->where('edition_id', $book->id)
            ->loanStatus('approved')
            ->whereNull('returned_at')
            ->first();
        $activeLoans = Loan::where('library_id', $library->id)
            ->where('edition_id', $book->id)
            ->loanStatus('approved')
            ->whereNull('returned_at')
            ->count();
        $edition_in_library = $library->editions()->find($book->id);
        if (!$edition_in_library) {
            return $this->setErrorResponse('Buku ini tidak tersedia di perpustakaan ini.', 404);
        }
        $stock_available = (int) $edition_in_library->pivot->stock_total - $activeLoans;
        if ($stock_available < 1) {
            return $this->setErrorResponse('Stok buku di perpustakaan ini sedang habis.', 400);
        }
        $data = $request->all();
        if ($existingPendingLoan) {
            return $this->setErrorResponse('Anda sudah melakukan pengajuan peminjaman untuk buku ini.', 400);
        }
        if ($existingNotReturnedLoan) {
            return $this->setErrorResponse('Anda sudah meminjam buku ini dan belum mengembalikannya.', 400);
        }
        $status = Status::where('type', 'loan')->where('name', 'pending')->first();
        if (!$status) {
            return $this->setErrorResponse('Status peminjaman tidak valid (pending tidak ditemukan). tolong hubungi administrator.', 500);
        }
        DB::beginTransaction();
        try {
            $user->loans()->create([
                'edition_id' => $book->id,
                'library_id' => $library->id,
                'loaned_at' => now(),
                'due_date' => now()->addWeeks($data['due_at']),
                'notes' => $data['notes'] ?? null,
                'status_id' => $status->id,
            ]);
            DB::commit();
            return $this->setResponse('Permintaan peminjaman dibuat.', null, 201);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal membuat permintaan peminjaman.', 500, $e->getMessage());
        }
    }
    public function list(LoanFilterRequest $request, Library $library, LoanListService $service)
    {
        $this->authorize('view', [Loan::class, $library]);
        $loans = $service->list($request, $library);
        $data = LoanResource::collection($loans);
        return $this->setResponse('Berhasil menampilkan daftar peminjaman.', $data);
    }
    public function approve(Request $request, Loan $loan)
    {
        $this->authorize('action', $loan);
        if ($loan->returned_at) {
            return $this->setErrorResponse('Peminjaman ini sudah dikembalikan.', 400);
        }
        if ($loan->status->name === 'approved') {
            return $this->setErrorResponse('Peminjaman ini sudah disetujui.', 400);
        }
        if ($loan->status->name === 'rejected') {
            return $this->setErrorResponse('Peminjaman ini sudah ditolak.', 400);
        }
        $edition_in_library = $loan->library->editions()->find($loan->edition_id);
        if (!$edition_in_library) {
            return $this->setErrorResponse('Buku ini tidak tersedia di perpustakaan ini.', 404);
        }
        $activeLoans = Loan::where('library_id', $loan->library_id)
            ->where('edition_id', $loan->edition_id)
            ->loanStatus('approved')
            ->whereNull('returned_at')
            ->count();
        $stock_available = (int) $edition_in_library->pivot->stock_total - $activeLoans;
        if ($stock_available < 1) {
            return $this->setErrorResponse('Stok buku di perpustakaan ini sedang habis.', 400);
        }
        $status = Status::where('type', 'loan')->where('name', 'approved')->first();
        if (!$status) {
            return $this->setErrorResponse('Status peminjaman tidak valid (approved tidak ditemukan). tolong hubungi administrator.', 500);
        }
        DB::beginTransaction();
        try {
            $loan->update([
                'status_id' => $status->id,
                'inspector_id' => $request->user('sanctum')->id,
                'inspected_at' => now(),
            ]);
            DB::commit();
            return $this->setResponse('Peminjaman disetujui.', null, 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menyetujui peminjaman.', 500, $e->getMessage());
        }
    }
    public function reject(Request $request, Loan $loan)
    {
        $this->authorize('action', $loan);
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        if ($loan->returned_at) {
            return $this->setErrorResponse('Peminjaman ini sudah dikembalikan.', 400);
        }
        if ($loan->status->name === 'rejected') {
            return $this->setErrorResponse('Peminjaman ini sudah ditolak.', 400);
        }
        if ($loan->status->name === 'approved') {
            return $this->setErrorResponse('Peminjaman ini sudah disetujui.', 400);
        }
        $status = Status::where('type', 'loan')->where('name', 'rejected')->first();
        if (!$status) {
            return $this->setErrorResponse('Status peminjaman tidak valid (rejected tidak ditemukan). tolong hubungi administrator.', 500);
        }
        DB::beginTransaction();
        try {
            $loan->update([
                'status_id' => $status->id,
                'inspector_id' => $request->user('sanctum')->id,
                'rejection_reason' => $request->input('reason', null),
                'inspected_at' => now(),
            ]);
            DB::commit();
            return $this->setResponse('Peminjaman ditolak.', null, 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menolak peminjaman.', 500, $e->getMessage());
        }
    }
    public function return(Request $request, Loan $loan)
    {
        $this->authorize('return', $loan);
        if ($loan->returned_at) {
            return $this->setErrorResponse('Peminjaman ini sudah dikembalikan.', 400);
        }
        if ($loan->status->name == 'pending') {
            return $this->setErrorResponse('Peminjaman ini belum disetujui.', 400);
        }
        if ($loan->status->name == 'rejected') {
            return $this->setErrorResponse('Peminjaman ini sudah ditolak.', 400);
        }
        $edition_in_library = $loan->library->editions()->find($loan->edition_id);
        if (!$edition_in_library) {
            return $this->setErrorResponse('Buku ini tidak tersedia di perpustakaan ini.', 404);
        }
        $returned_status = Status::where('type', 'loan')->where('name', 'returned')->first()->id;
        if (!$returned_status) {
            return $this->setErrorResponse('Status pengembalian tidak ditemukan.', 404);
        }
        DB::beginTransaction();
        try {
            $loan->update([
                'returned_at' => now(),
                'status_id' => $returned_status,
            ]);
            $loan->loadMissing('borrower');
            $borrower = $loan->borrower;
            if ($borrower) {
                $hasOverdue = Loan::query()
                    ->ownedBy($borrower->id)
                    ->overdue()
                    ->exists();
                if (!$hasOverdue && $borrower->hasRole('Blacklist')) {
                    $borrower->removeRole('Blacklist');
                }
            }
            DB::commit();
            return $this->setResponse('Buku berhasil dikembalikan.', null, 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal mengembalikan buku.', 500, $e->getMessage());
        }
    }
    public function show(Request $request, Loan $loan)
    {
        $this->authorize('action', $loan);

        $loan->loadMissing([
            'library:id,name',
            'edition:id,subtitle,isbn_10,isbn_13,book_title_id',
            'edition.title:id,title',
            'borrower:id,nickname,email',
            'borrower.identity',
            'inspector:id,nickname',
            'status:id,type,name,description',
        ]);
        $this->authorize('view', $loan->borrower->identity);
        $data = new LoanDetailResource($loan);
        return $this->setResponse('Berhasil menampilkan detail peminjaman.', $data);
    }
}
