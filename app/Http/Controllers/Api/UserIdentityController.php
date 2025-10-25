<?php
namespace App\Http\Controllers\Api;
use App\Rules\UniqueNikRule;
use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserIdentityRequest;
use App\Http\Requests\UserIdentityUpdateRequest;
use App\Http\Resources\IdentityResource;
use App\Models\User;
use App\Models\UserIdentity;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserIdentityController extends Controller
{
    use AuthorizesRequests;
    use ApiResponse;
    public function store(UserIdentityRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->setResponse('Unauthorized', null, 401, 'error');
        }
        $loans = $user->loans();
        $loanCount = $loans->count();
        $canUpdate = $loanCount == 0;
        if (!$canUpdate) {
            return $this->setErrorResponse('Anda tidak bisa memperbarui data identitas saat ini.', 403);
        }
        DB::beginTransaction();
        $imagePath = null;
        $file = $request->file('identity_image');
        try {
            $fileName = $user->id . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('identity_images', $fileName, 'private');
            $fullImagePath = Storage::disk('private')->path($imagePath);
            $base = rtrim(env('PYTHON_OCR_API_URL', config('app.python_ocr_api_url', 'http://127.0.0.1:8001')), '/');
            $response = Http::timeout(60)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-API-Key' => env('PYTHON_OCR_API_KEY', 'super_secret_python_key_123abcXYZ'),
                ])
                ->attach(
                    'identity_image',
                    fopen($fullImagePath, 'r'),
                    $file->getClientOriginalName()
                )
                ->post($base . '/process-ktp');
            if ($response->successful()) {
                $ocrResult = $response->json();
                $ocrResult = $response->json()['data']['ocr_result'] ?? null;
                if (!$ocrResult) {
                    DB::rollBack();
                    if ($imagePath && Storage::disk('private')->exists($imagePath)) {
                        Storage::disk('private')->delete($imagePath);
                    }
                    return $this->setResponse('Format respons dari API OCR tidak sesuai harapan.', null, 500, 'error');
                }
                $phoneNumber = $request->input('phone');
                $dataToStore = [
                    'user_id' => $user->id,
                    'full_name' => strtoupper($ocrResult['nama']),
                    'nik' => $ocrResult['nik'],
                    'birth_place' => strtoupper($ocrResult['tempat_lahir']),
                    'address' => $ocrResult['alamat_lengkap'],
                    'gender' => convert_gender_to_standard($ocrResult['jenis_kelamin']) ?? 'male',
                    'phone_number' => $phoneNumber,
                    'identity_image_path' => $imagePath,
                ];

                if ($request->input('relationship') != null) {
                    $dataToStore['relationship'] = $request->input('relationship');
                }
                Log::info('hasil ocr', $ocrResult);
                $validator = Validator::make([
                    'nik' => $dataToStore['nik'],
                ], [
                    'nik' => ['required', 'string', 'digits:16', new UniqueNikRule($user->id)],
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    if ($imagePath && Storage::disk('private')->exists($imagePath)) {
                        Storage::disk('private')->delete($imagePath);
                    }
                    return $this->setErrorResponse('Data OCR tidak valid silahkan foto ulang KTP', 503); // Mengembalikan error validasi NIK
                }

                $birthDate = $ocrResult['tanggal_lahir'];

                if (isset($ocrResult['tanggal_lahir']) && $ocrResult['tanggal_lahir'] !== 'UNKNOWN') {
                    try {
                        $carbonDate = Carbon::createFromFormat('d-m-Y', $ocrResult['tanggal_lahir']);
                        if ($carbonDate && $carbonDate->isValid()) {
                            $birthDate = $carbonDate->format('Y-m-d');
                        } else {
                            return $this->setErrorResponse('Data OCR tidak valid silahkan foto ulang KTP', 503);
                        }
                    } catch (Exception $e) {
                        return $this->setErrorResponse('Data OCR tidak valid silahkan foto ulang KTP', 503);
                    }
                }
                $dataToStore['birth_date'] = $birthDate;

                $identity = UserIdentity::updateOrCreate(
                    ['user_id' => $user->id],
                    $dataToStore
                );
                $user->assignRole('Completed Identity');
                DB::commit();
                return $this->setResponse('Data identitas berhasil disimpan!', new IdentityResource($identity), 200, 'success');
            } else {
                DB::rollBack();
                if ($imagePath && Storage::disk('private')->exists($imagePath)) {
                    Storage::disk('private')->delete($imagePath);
                }
                $errorData = $response->json();
                $errorMessage = $errorData['detail'] ?? 'Terjadi kesalahan tidak diketahui dari API OCR.';
                return $this->setErrorResponse($errorMessage, 500, $response->json());
            }
        } catch (ConnectionException $e) {
            DB::rollBack();
            if ($imagePath && Storage::disk('private')->exists($imagePath)) {
                Storage::disk('private')->delete($imagePath);
            }
            return $this->setErrorResponse('Tidak dapat terhubung ke server OCR. Silakan coba lagi nanti', 503, $e->getMessage());
        } catch (Throwable $e) {
            DB::rollBack();
            if ($imagePath) {
                Storage::disk('private')->delete($imagePath);
            }
            return $this->setErrorResponse('Terjadi kesalahan internal pada server', 500, $e->getMessage());
        }
    }
    public function update(UserIdentityUpdateRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->setResponse('Unauthorized', null, 401, 'error');
        }
        $identity = $user->identity;
        if (!$identity) {
            return $this->setResponse('Data identitas tidak ditemukan.', null, 404);
        }

        $loans = $user->loans();
        $loanCount = $loans->count();
        $canUpdate = $loanCount == 0;

        if (!$canUpdate) {
            return $this->setErrorResponse('Anda tidak bisa memperbarui data identitas saat ini.', 403);
        }

        $this->authorize('update', $identity);

        DB::beginTransaction();
        try {
            $data = $request->only([
                'nik',
                'birth_date',
                'phone',
            ]);
            $data['full_name'] = strtoupper($request->input('full_name'));
            $data['birth_place'] = strtoupper($request->input('birth_place'));
            $data['address'] = strtoupper($request->input('address'));
            $data['gender'] = convert_gender_to_standard($request->input('gender'));
            $data['has_own_ktp'] = $request->boolean('has_own_ktp');

            if ($data['has_own_ktp']) {
                $data['relationship'] = null;
            } else {
                if ($request->filled('relationship')) {
                    $data['relationship'] = $request->input('relationship');
                }
            }
            $identity->update($data);
            DB::commit();
            return $this->setResponse('Data identitas berhasil diperbarui!', new IdentityResource($identity), 200, 'success');
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->setErrorResponse('Terjadi kesalahan saat memperbarui data identitas.', 500, $e->getMessage());
        }

    }
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->setResponse('Unauthorized. Pengguna tidak ditemukan.', null, 401);
        }
        $identity = $user->identity;
        if ($identity) {
            $this->authorize('view', $identity);
            return $this->setResponse('Data identitas pribadi ditemukan.', new IdentityResource($identity));
        }
        $guardian = $user->guardian;
        if ($guardian) {
            $this->authorize('viewGuardianData', $guardian);
            return $this->setResponse('Data identitas wali ditemukan.', new IdentityResource($guardian));
        }
        return $this->setResponse('Data identitas belum ditemukan.', null, 404);
    }
    public function serveIdentityImage(Request $request, User $user)
    {
        $user->load('identity');
        if (!$user->identity) {
            abort(401);
        }
        $identity = $user->identity;
        $this->authorize('view', $identity);
        $path = $identity->identity_image_path;
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }
        $fullPath = Storage::disk('private')->path($path);
        $mime = mime_content_type($fullPath) ?: 'image/jpeg';
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="identity"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
