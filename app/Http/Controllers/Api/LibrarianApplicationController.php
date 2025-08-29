<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibrarianApplicationController extends Controller
{
    use ApiResponse;
    public function store(Library $library)
    {
        $user = auth('sanctum')->user();

        DB::beginTransaction();
        try {
            $already = $library->librarians()->where('users.id', $user->id)->exists();
            if ($already) {
                return $this->setResponse('Kamu sudah terdaftar sebagai pustakawan di perpustakaan ini.', null, 409);
            }
            $hasPending = $library->librarianApplications()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();
            if ($hasPending) {
                return $this->setResponse('Pengajuan sebelumnya masih diproses.', null, 409);
            }
            $library->librarianApplications()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
            DB::commit();
            return $this->setResponse('Berhasil mengajukan permohonan menjadi pustakawan.');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal mengajukan permohonan menjadi pustakawan.', 500);
        }
    }
}
