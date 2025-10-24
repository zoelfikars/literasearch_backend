<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipFilterRequest;
use App\Http\Resources\MembershipInspectResource;
use App\Http\Resources\MembershipUnifiedResource;
use App\Models\Library;
use App\Models\LibraryMember;
use App\Models\MembershipApplication;
use App\Models\Status;
use App\Models\User;
use App\Services\MembershipListService;
use App\Traits\ApiResponse;
use Auth;
use DB;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Throwable;

class MembershipController extends Controller
{
    use AuthorizesRequests;
    use ApiResponse;
    public function store(Library $library, Request $request)
    {
        $this->authorize('apply', $library);
        if (!$library->is_active) {
            return $this->setErrorResponse('Perpustakaan belum aktif.', 500);
        }
        $user = $request->user();
        $pendingStatus = Status::where('type', 'membership_application')
            ->where('name', 'pending')
            ->value('id');
        if (!$pendingStatus) {
            return $this->setErrorResponse('Status "pending" belum tersedia. Jalankan seeder statuses.', 500);
        }
        DB::beginTransaction();
        try {
            $isActiveMember = DB::table('library_members')
                ->where('library_id', $library->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()->exists();
            if ($isActiveMember) {
                DB::rollBack();
                return $this->setErrorResponse('Anda sudah terdaftar sebagai member aktif di perpustakaan ini.', 409);
            }
            $hasPending = DB::table('membership_applications')
                ->where('library_id', $library->id)
                ->where('user_id', $user->id)
                ->where('status_id', $pendingStatus)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->exists();
            if ($hasPending) {
                DB::rollBack();
                return $this->setErrorResponse('Pengajuan sebelumnya masih menunggu verifikasi.', 409);
            }
            MembershipApplication::create([
                'user_id' => $user->id,
                'library_id' => $library->id,
                'status_id' => $pendingStatus,
            ]);
            DB::commit();
            return $this->setResponse("Pengajuan membership ke perpustakaan {$library->name} berhasil dibuat", null, 201);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function list(MembershipFilterRequest $request, MembershipListService $service, string $library)
    {
        $this->authorize('view', [MembershipApplication::class, $library]);
        $user = $request->user('sanctum');
        $section = $request->get('section', 'members');
        if ($section === 'applications') {
            $apps = $service->listApplications($request, $user, $library);
            $data = MembershipUnifiedResource::collection($apps);
            return $this->setResponse('Berhasil menampilkan pengajuan member.', $data);
        }

        $members = $service->listMembers($request, $user, $library);
        $data = MembershipUnifiedResource::collection($members);
        return $this->setResponse('Berhasil menampilkan member perpustakaan.', $data);
    }
    public function show(MembershipApplication $application)
    {
        $this->authorize('view', [$application->user->identity]);
        $membership_application = $application->load(['user.identity']);
        $data = new MembershipInspectResource($membership_application);
        return $this->setResponse('Berhasil menampilkan detail member.', $data);
    }
    public function approve(MembershipApplication $application)
    {
        $this->authorize('approve', $application);
        $authUser = Auth::user();
        $allowedStatuses = ['pending'];
        if (!in_array($application->status->name, $allowedStatuses)) {
            return $this->setErrorResponse('Pengajuan membership sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'membership_application')->where('name', 'approved')->first();
            $application->update([
                'status_id' => $status->id,
                'inspector_id' => $authUser->id,
                'inspected_at' => now(),
            ]);
            $library = $application->library;
            if ($library) {
                $application->load('user');
                $user = $application->user;
                $user->membership()->syncWithoutDetaching([
                    $library->id => ['is_active' => true]
                ]);
                $application->user->assignRole('Member');
                $application->save();
            }
            DB::commit();
            return $this->setResponse('Pengajuan membership berhasil disetujui', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menyetujui pengajuan membership', 500, $e->getMessage());
        }
    }
    public function reject(MembershipApplication $application, Request $request)
    {
        $this->authorize('reject', $application);
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        $reason = $request->input('reason');
        $authUser = Auth::user();
        $allowedStatuses = ['pending'];
        if (!in_array($application->status->name, $allowedStatuses)) {
            return $this->setErrorResponse('Pengajuan membership sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'membership_application')->where('name', 'rejected')->first();
            $application->update([
                'status_id' => $status->id,
                'inspector_id' => $authUser->id,
                'inspected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            DB::commit();
            return $this->setResponse('Pengajuan membership berhasil ditolak', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menolak pengajuan membership', 500, $e->getMessage());
        }
    }
    public function activate(Library $library, User $user)
    {
        $member = LibraryMember::where('library_id', $library->id)->where('user_id', $user->id)->first();
        $this->authorize('manage', $member);
        DB::beginTransaction();
        try {
            $user->membership()->syncWithoutDetaching([
                $library->id => ['is_active' => true]
            ]);
            DB::commit();
            return $this->setResponse('Membership berhasil diaktifkan', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal mengaktifkan membership', 500, $e->getMessage());
        }
    }
    public function deactivate(Library $library, User $user)
    {
        $member = LibraryMember::where('library_id', $library->id)->where('user_id', $user->id)->first();
        $this->authorize('manage', $member);
        DB::beginTransaction();
        try {
            $user->membership()->syncWithoutDetaching([
                $library->id => ['is_active' => false]
            ]);
            DB::commit();
            return $this->setResponse('Membership berhasil dinonaktifkan', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menonaktifkan membership', 500, $e->getMessage());
        }
    }
    public function blacklist(Library $library, User $user)
    {
        // jangan dipake. biar otomatis ngapus blacklistnya pas return loan.
        $member = LibraryMember::where('library_id', $library->id)->where('user_id', $user->id)->first();
        $this->authorize('manage', $member);
        DB::beginTransaction();
        try {
            $user->membership()->syncWithoutDetaching([
                $library->id => ['is_blacklist' => true]
            ]);
            $user->assignRole('Blacklist');
            DB::commit();
            return $this->setResponse('Berhasil menambahkan blacklist kepada pengguna', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat memberikan blacklist kepada pengguna', 500, $e->getMessage());
        }
    }
    public function unblacklist(Library $library, User $user)
    {
        // jangan dipake. biar otomatis ngapus blacklistnya pas return loan.
        $member = LibraryMember::where('library_id', $library->id)->where('user_id', $user->id)->first();
        $this->authorize('manage', $member);
        DB::beginTransaction();
        try {
            $user->membership()->syncWithoutDetaching([
                $library->id => ['is_blacklist' => false]
            ]);
            $user->removeRole('Blacklist');
            DB::commit();
            return $this->setResponse('Blacklist pengguna berhasil dihapus', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menghapus blacklist pengguna', 500, $e->getMessage());
        }
    }
}
