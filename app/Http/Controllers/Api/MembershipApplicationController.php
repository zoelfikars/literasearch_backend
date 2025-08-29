<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\DB;

class MembershipApplicationController extends Controller
{
    use ApiResponse;
    public function store(Library $library)
    {
        $user = auth('sanctum')->user();

        DB::beginTransaction();
        try {
            $library->membershipApplications()->create([
                'user_id' => $user->id,
            ]);
            DB::commit();
            return $this->setResponse('Berhasil mengajukan pendaftaran membership.', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal mengajukan pendaftaran membership.', 500, $e->getMessage());
        }
    }
}
