<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryApplicationStoreRequest;
use App\Http\Requests\RejectionRequest;
use App\Http\Resources\LibraryApplicationResource;
use App\Models\Library;
use App\Models\LibraryApplication;
use App\Models\Status;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class LibraryApplicationController extends Controller
{
    use AuthorizesRequests;
    use ApiResponse;
    public function show($id)
    {
        $application = LibraryApplication::with('user.identity', 'status', 'reviewer', 'library')->find($id);
        if (!$application) {
            return $this->setResponse('Data pengajuan tidak ditemukan', null, 404);
        }
        $this->authorize('viewIdentityData', $application->user->identity);
        $this->authorize('viewLibraryApplicationDocument', $application);
        return $this->setResponse('Detail pengajuan berhasil diambil', new LibraryApplicationResource($application), 200);
    }
    public function store(LibraryApplicationStoreRequest $request)
    {
        $validatedData = $request->validated();
        $documentPath = null;
        $imagePath = null;

        DB::beginTransaction();
        try {
            $user = $request->user();
            $statusId = Status::where('type', 'library_application')->where('name', 'pending')->value('id');
            $library = Library::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'address' => $validatedData['address'],
                'phone_number' => $validatedData['phone'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'owner_id' => $user->id,
            ]);
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imagePath = $file->storeAs('libraries/cover/', $library->id . '.' . $file->getClientOriginalExtension(), 'private');
            }
            $library->image_path = $imagePath;
            $library->save();
            $libraryId = $library->id;
            $application = LibraryApplication::create([
                'library_id' => $libraryId,
                'expiration_date' => $validatedData['expiration_date'],
                'user_id' => $user->id,
                'status_id' => $statusId,
            ]);
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $documentPath = $file->storeAs('libraries/applications/', $application->id . '.' . $file->getClientOriginalExtension(), 'private');
            }
            $application->document_path = $documentPath;
            $application->save();
            DB::commit();
            return $this->setResponse('Pengajuan perpustakaan berhasil dikirim', null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($documentPath) {
                Storage::disk('private')->delete($documentPath);
            }
            if ($imagePath) {
                Storage::disk('private')->delete($imagePath);
            }
            return $this->setErrorResponse('Pengajuan perpustakaan gagal', 500, $e->getMessage());
        }
    }
    public function approve($id)
    {
        $application = LibraryApplication::find($id);
        $authUser = Auth::user();
        if (!$application) {
            return $this->setErrorResponse('Data pengajuan tidak ditemukan', 404);
        }
        $allowedStatuses = ['pending'];
        if (!in_array($application->status->name, $allowedStatuses)) {
            return $this->setErrorResponse('Pengajuan sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'library_application')->where('name', 'approved')->first();
            $application->update([
                'status_id' => $status->id,
                'reviewed_by' => $authUser->id,
            ]);
            $library = $application->library;
            if ($library) {
                $library->load('applications');
                $applicationLength = $library->applications->count();
                if (!($applicationLength > 1)) {
                    $application->load('user');
                    $user = $application->user;
                    $user->managedLibraries()->syncWithoutDetaching([
                        $library->id => ['is_active' => true]
                    ]);
                }
                $library->is_active = true;
                $library->save();
            }
            $application->user->assignRole('Pustakawan');
            DB::commit();
            return $this->setResponse('Pengajuan berhasil disetujui', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menyetujui pengajuan', 500, $e->getMessage());
        }
    }
    public function reject($id, RejectionRequest $request)
    {
        $application = LibraryApplication::find($id);
        $authUser = Auth::user();
        if (!$application) {
            return $this->setErrorResponse('Data pengajuan tidak ditemukan', 404);
        }
        if ($application->status->name !== 'pending') {
            return $this->setErrorResponse('Pengajuan sudah diproses sebelumnya', 422);
        }
        DB::beginTransaction();
        try {
            $status = Status::where('type', 'library_application')->where('name', 'rejected')->first();
            $application->update([
                'status_id' => $status->id,
                'reviewed_by' => $authUser->id,
                'rejected_reason' => $request->input('reason'),
            ]);
            $library = $application->library;
            if ($library) {
                $library->load('applications');
                $applicationLength = $library->applications->count();
                if ($applicationLength == 1) {
                    $library->delete();
                }
            }
            DB::commit();
            return $this->setResponse('Pengajuan berhasil ditolak', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->setErrorResponse('Gagal saat menolak pengajuan', 500, $e->getMessage());
        }
    }
    public function serveLibraryApplicationDocument(LibraryApplication $application)
    {
        $this->authorize('viewLibraryApplicationDocument', $application);
        $path = $application->document_path;
        $imageExist = Storage::disk('private')->exists($path);
        if (!$imageExist) {
            abort(404);
        }
        return response()->file(
            Storage::disk('private')->path($application->document_path),
        );
    }
}
