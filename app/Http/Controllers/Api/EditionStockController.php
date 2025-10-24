<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\LibraryEdition;
use App\Models\Library;
use App\Models\Loan;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Throwable;
class EditionStockController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function store(Library $library, Edition $book, Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $amount = (int) $data['amount'];
        $this->authorize('manage', [LibraryEdition::class, $library]);
        DB::beginTransaction();
        try {
            $created = LibraryEdition::create([
                'library_id' => $library->id,
                'edition_id' => $book->id,
                'stock_total' => $amount,
            ]);
            DB::commit();
            return $this->setResponse('Stok dibuat.', $created, 201);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;
            $isUniqueViolation = ($driverCode === 1062 || $sqlState === '23505' || $driverCode === 19);
            if (!$isUniqueViolation) {
                DB::rollBack();
                return $this->setErrorResponse('Gagal menambahkan stok.', 500, $e->getMessage());
            }
            DB::rollBack();
            DB::beginTransaction();
            DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->lockForUpdate()
                ->update([
                    'stock_total' => DB::raw("stock_total + {$amount}"),
                    'updated_at' => now(),
                ]);
            $row = LibraryEdition::where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->firstOrFail();
            DB::commit();
            return $this->setResponse('Stok ditambahkan.', [
                'library_id' => $row->library_id,
                'edition_id' => $row->edition_id,
                'stock_total' => (int) $row->stock_total,
                'updated_at' => $row->updated_at,
            ], 200);
        }
    }
    public function add(Library $library, Edition $book)
    {
        $this->authorize('manage', [LibraryEdition::class, $library]);
        DB::beginTransaction();
        try {
            $row = DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->lockForUpdate()
                ->first();
            if (!$row) {
                DB::rollBack();
                return $this->setErrorResponse('Baris stok belum ada. Gunakan endpoint store untuk membuatnya terlebih dahulu.', 404);
            }
            DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->update([
                    'stock_total' => DB::raw('stock_total + 1'),
                    'updated_at' => now(),
                ]);
            $updated = DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->first();
            DB::commit();
            return $this->setResponse('Berhasil menambahkan stok', [
                'library_id' => $library->id,
                'edition_id' => $book->id,
                'added' => 1,
                'stock_total' => (int) $updated->stock_total,
                'updated_at' => $updated->updated_at,
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menambahkan stok', 500);
        }
    }
    public function remove(Library $library, Edition $book)
    {
        $this->authorize('manage', [LibraryEdition::class, $library]);
        DB::beginTransaction();
        try {
            $row = DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->lockForUpdate()
                ->first();
            if (!$row) {
                DB::rollBack();
                return $this->setErrorResponse(
                    'Baris stok belum ada. Gunakan endpoint store untuk membuatnya terlebih dahulu.',
                    404
                );
            }
            $activeLoans = Loan::where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->loanStatus('approved')
                ->whereNull('returned_at')
                ->count();
            if ((int) $row->stock_total <= $activeLoans) {
                DB::rollBack();
                return $this->setErrorResponse(
                    'Stok tidak bisa dikurangi karena akan lebih kecil dari jumlah peminjaman aktif.',
                    409
                );
            }
            DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->update([
                    'stock_total' => DB::raw('stock_total - 1'),
                    'updated_at' => now(),
                ]);
            $updated = DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->first();
            DB::commit();
            $stockAvailable = (int) $updated->stock_total - $activeLoans;
            return $this->setResponse('Berhasil mengurangi stok', null, 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal mengurangi stok', 500);
        }
    }
    public function purgeAll(Library $library, Edition $book)
    {
        $this->authorize('manage', [LibraryEdition::class, $library]);
        DB::beginTransaction();
        try {
            $row = DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->lockForUpdate()
                ->first();
            if (!$row) {
                DB::rollBack();
                return $this->setErrorResponse(
                    'Baris stok belum ada. Gunakan endpoint store untuk membuatnya terlebih dahulu.',
                    404
                );
            }
            $activeLoans = Loan::where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->loanStatus('approved')
                ->whereNull('returned_at')
                ->count();
            if ($activeLoans > 0) {
                DB::rollBack();
                return $this->setErrorResponse(
                    'Tidak dapat menghapus seluruh stok karena masih ada peminjaman aktif.',
                    409
                );
            }
            DB::table('library_editions')
                ->where('library_id', $library->id)
                ->where('edition_id', $book->id)
                ->delete();
            DB::commit();
            return $this->setResponse('Berhasil menghapus seluruh stok (baris dihapus).', null, 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus seluruh stok.', 500, app()->isLocal() ? $e->getMessage() : null);
        }
    }
}
