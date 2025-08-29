<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryApplicationExtendRequest;
use App\Http\Requests\LibraryEditRequest;
use App\Http\Resources\LibraryApplicationResource;
use App\Http\Resources\LibraryResource;
use App\Models\Library;
use App\Models\LibraryApplication;
use App\Models\Status;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    public function update(LibraryEditRequest $request, $id)
    {
        $library = Library::find($id);
        if (empty($library)) {
            return $this->setErrorResponse("Data perpustakaan tidak ditemukan", 404);
        }
        $this->authorize('editLibrary', $library);
        DB::beginTransaction();
        $imagePath = null;
        try {
            $data = [
                "name" => $request->input("name", $library->name),
                "address" => $request->input("address", $library->address),
                "description" => $request->input("description", $library->description),
                "phone_number" => $request->input("phone", $library->phone_number),
                "latitude" => $request->input("latitude", $library->latitude),
                "longitude" => $request->input("longitude", $library->longitude),
                "is_recruiting" => $request->input("is_recruiting", $library->is_recruiting),
            ];
            $file = $request->file("image");
            if ($file) {
                $fileName = $id . "." . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs('libraries/cover', $fileName, 'private');
                $data['image_path'] = $imagePath;
            }

            $library->update($data);
            $data = $library->load('latestApprovedByExpiration');
            DB::commit();
            return $this->setResponse("Data perpustakaan berhasil diperbarui", new LibraryResource($data), 200);
        } catch (Exception $e) {
            DB::rollBack();
            if ($imagePath) {
                Storage::disk('private')->delete($imagePath);
            }
            return $this->setErrorResponse("Gagal memperbarui data perpustakaan", 500, $e->getMessage());
        }
    }
    public function extend(LibraryApplicationExtendRequest $request)
    {
        // reminder disini nanti cek dlu kalo masih ada yang masih pending bilang harus diacc dlu
        $validatedData = $request->validated();
        $documentPath = null;
        if ($request->hasFile("document")) {
            $file = $request->file("document");
            $documentPath = $file->store("applications/libraries", "private");
        }
        DB::beginTransaction();
        try {
            $statusId = Status::where("type", "library_application")->where("name", "pending")->value("id");
            $libraryId = $validatedData["library_id"];
            $library = Library::find($libraryId);
            if (empty($library)) {
                return $this->setErrorResponse("Data perpustakaan tidak ditemukan", 404);
            }
            $library_extend = $library->latestApprovedByExpiration();

            if (!empty($library_extend)) {
                $expired = Carbon::parse($library_extend->expiration_date)->isPast();
                if (!$expired) {
                    return $this->setErrorResponse(
                        "Pengajuan hanya bisa jika dokumen sudah kadaluarsa.",
                        422
                    );
                }
            }
            $application = LibraryApplication::create([
                "library_id" => $libraryId,
                "document_path" => $documentPath,
                "expiration_date" => $validatedData["expiration_date"],
                "user_id" => $request->user()->id,
                "status_id" => $statusId,
            ]);
            DB::commit();
            return $this->setResponse("Pengajuan perpanjangan perpustakaan berhasil dikirim", new LibraryApplicationResource($application->load("status")), 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($documentPath) {
                Storage::disk("private")->delete($documentPath);
            }
            return $this->setErrorResponse("Pengajuan perpanjangan perpustakaan gagal", 500, $e->getMessage());
        }
    }
}
