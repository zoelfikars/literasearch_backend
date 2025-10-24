<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibrarianFilterRequest;
use App\Http\Resources\LibrarianInspectResource;
use App\Http\Resources\LibrarianUnifiedResource;
use App\Models\LibrarianApplication;
use App\Models\Library;
use App\Models\LibraryLibrarian;
use App\Models\Status;
use App\Models\User;
use App\Services\LibrarianListService;
use App\Traits\ApiResponse;
use Auth;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class LibrarianController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function store(Library $library, Request $request)
    {
        $this->authorize('apply', $library);
        if (!$library->is_active) {
            return $this->setErrorResponse('Perpustakaan belum aktif.', 500);
        }
        $user = $request->user();
        if (!$user) {
            return $this->setErrorResponse('Unauthenticated.', 401);
        }
        $pendingStatus = Status::where('type', 'librarian_application')
            ->where('name', 'pending')
            ->value('id');
        if (!$pendingStatus) {
            return $this->setErrorResponse('Status pending belum tersedia. Jalankan seeder statuses.', 500);
        }
        DB::beginTransaction();
        try {
            $isActiveMember = DB::table('library_librarians')
                ->where('library_id', $library->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()->exists();
            if ($isActiveMember) {
                DB::rollBack();
                return $this->setErrorResponse('Anda sudah terdaftar sebagai pustakawan aktif di perpustakaan ini.', 409);
            }
            $hasPending = DB::table('librarian_applications')
                ->where('library_id', $library->id)
                ->where('user_id', $user->id)
                ->where('status_id', $pendingStatus)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->exists();
            if ($hasPending) {
                DB::rollBack();
                return $this->setErrorResponse('Pengajuan pustakawan sebelumnya masih menunggu verifikasi.', 409);
            }
            LibrarianApplication::create([
                'user_id' => $user->id,
                'library_id' => $library->id,
                'status_id' => $pendingStatus,
            ]);
            DB::commit();
            return $this->setResponse("Pengajuan pustakawan ke perpustakaan {$library->name} berhasil dibuat", null, 201);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function list(LibrarianFilterRequest $request, LibrarianListService $service, string $library)
    {
        $this->authorize('view', [LibrarianApplication::class, $library]);
        $user = $request->user('sanctum');
        $section = $request->get('section', 'librarians');

        if ($section === 'applications') {
            $apps = $service->listApplications($request, $user, $library);
            $data = LibrarianUnifiedResource::collection($apps);
            return $this->setResponse('Berhasil menampilkan pendaftaran pustakawan.', $data);
        }

        $librarians = $service->list($request, $user, $library);
        $data = LibrarianUnifiedResource::collection($librarians);
        return $this->setResponse('Berhasil menampilkan pustakawan perpustakaan.', $data);
    }
    public function show(LibrarianApplication $application)
    {
        $this->authorize('view', [$application->user->identity]);
        $librarian_application = $application->load(['user', 'user.identity', 'status']);
        $data = new LibrarianInspectResource($librarian_application);
        return $this->setResponse('Berhasil menampilkan detail pelamar pustakawan.', $data);
    }
    public function approve(LibrarianApplication $application)
    {
        $this->authorize('approve', $application);
        $authUser = Auth::user();
        $allowedStatuses = ['pending'];
        if (!in_array($application->status->name, $allowedStatuses)) {
            return $this->setErrorResponse('Pengajuan pustakawan sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'librarian_application')->where('name', 'approved')->first();
            $application->update([
                'status_id' => $status->id,
                'inspector_id' => $authUser->id,
                'inspected_at' => now(),
            ]);
            $library = $application->library;
            if ($library) {
                $application->load('user');
                $user = $application->user;
                $user->librarian()->syncWithoutDetaching([
                    $library->id => ['is_active' => true]
                ]);
                $application->user->assignRole('Pustakawan');
                $application->save();
            }
            DB::commit();
            return $this->setResponse('Pengajuan pustakawan berhasil disetujui', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menyetujui pengajuan pustakawan', 500, $e->getMessage());
        }
    }
    public function reject(LibrarianApplication $application, Request $request)
    {
        $this->authorize('reject', $application);
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        $reason = $request->input('reason');
        $authUser = Auth::user();
        $allowedStatuses = ['pending'];
        if (!in_array($application->status->name, $allowedStatuses)) {
            return $this->setErrorResponse('Pengajuan pustakawan sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'librarian_application')->where('name', 'rejected')->first();
            $application->update([
                'status_id' => $status->id,
                'inspector_id' => $authUser->id,
                'inspected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            DB::commit();
            return $this->setResponse('Pengajuan pustakawan berhasil ditolak', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menolak pengajuan pustakawan', 500, $e->getMessage());
        }
    }
    public function activate(Library $library, User $user)
    {
        $librarian = LibraryLibrarian::where('library_id', $library->id)->where('user_id', $user->id)->first();
        $this->authorize('manage', $librarian);
        DB::beginTransaction();
        try {
            $user->librarian()->syncWithoutDetaching([
                $library->id => ['is_active' => true]
            ]);
            DB::commit();
            return $this->setResponse('Pustakawan berhasil diaktifkan', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal mengaktifkan pustakawan', 500, $e->getMessage());
        }
    }
    public function deactivate(Library $library, User $user)
    {
        $librarian = LibraryLibrarian::where('library_id', $library->id)->where('user_id', $user->id)->first();
        if ($user->id === $library->owner_id) {
            return $this->setErrorResponse('Tidak dapat menonaktifkan pustakawan karena dia adalah pemilik perpustakaan', 422);
        }
        $this->authorize('manage', $librarian);
        DB::beginTransaction();
        try {
            $user->librarian()->syncWithoutDetaching([
                $library->id => ['is_active' => false]
            ]);
            DB::commit();
            return $this->setResponse('Pustakawan berhasil dinonaktifkan', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal menonaktifkan pustakawan', 500, $e->getMessage());
        }
    }
}
