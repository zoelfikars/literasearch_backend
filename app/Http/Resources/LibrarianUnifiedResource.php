<?php
namespace App\Http\Resources;
use App\Models\LibraryLibrarian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class LibrarianUnifiedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isLibrarianModel = $this->resource instanceof LibraryLibrarian;
        if ($isLibrarianModel) {
            $userData = $this->relationLoaded('user') && !empty($this->user) ? [
                'id' => $this->user->id,
                'nickname' => $this->user->nickname,
                'full_name' => $this->user->relationLoaded('identity') && !empty($this->user->identity) ? $this->user->identity->full_name : null,
            ] : null;
            $approvedApp = $this->relationLoaded('approvedApplication') && !empty($this->approvedApplication)
                ? $this->approvedApplication
                : null;
            $firstApproved = ($this->relationLoaded('library') && !empty($this->library) && $this->library->relationLoaded('firstApproved'))
                ? $this->library->firstApproved
                : null;
            $statusModel = null;
            if ($approvedApp && $approvedApp->relationLoaded('status') && !empty($approvedApp->status)) {
                $statusModel = $approvedApp->status;
            } elseif ($firstApproved && $firstApproved->relationLoaded('status') && !empty($firstApproved->status)) {
                $statusModel = $firstApproved->status;
            }
            $inspectorData = null;
            if ($approvedApp && $approvedApp->relationLoaded('inspector') && !empty($approvedApp->inspector)) {
                $inspectorData = [
                    'id' => $approvedApp->inspector->id,
                    'nickname' => $approvedApp->inspector->nickname,
                    'inspected_at' => optional($approvedApp->inspected_at)->toIso8601String(),
                ];
            } elseif ($firstApproved && $firstApproved->relationLoaded('inspector') && !empty($firstApproved->inspector)) {
                $inspectorData = [
                    'id' => $firstApproved->inspector->id,
                    'nickname' => $firstApproved->inspector->nickname,
                    'inspected_at' => optional($firstApproved->inspected_at)->toIso8601String(),
                ];
            }
            $appliedAt = null;
            if ($approvedApp) {
                $appliedAt = optional($approvedApp->created_at)->toIso8601String();
            } elseif ($firstApproved) {
                $appliedAt = optional($firstApproved->created_at)->toIso8601String();
            }
            $data = [
                'is_active' => $this->is_active === 1 ? true : false,
                'applied_at' => $appliedAt,
                'applicant' => $userData,
                'status' => $statusModel ? new StatusResource($statusModel) : null,
                'inspector' => $inspectorData,
            ];
            return $data;
        }
        $userData = $this->relationLoaded('user') && !empty($this->user) ? [
            'id' => $this->user->id,
            'nickname' => $this->user->nickname,
            'full_name' => $this->user->relationLoaded('identity') && !empty($this->user->identity) ? $this->user->identity->full_name : null,
        ] : null;
        $statusModel = $this->relationLoaded('status') && !empty($this->status) ? new StatusResource($this->status) : null;
        $inspectorData = $this->relationLoaded('inspector') && !empty($this->inspector) ? [
            'id' => $this->inspector->id,
            'nickname' => $this->inspector->nickname,
            'inspected_at' => optional($this->inspected_at)->toIso8601String(),
        ] : null;
        $appliedAt = optional($this->created_at)->toIso8601String();
        $data = [
            'id' => $this->id,
            'is_active' => false,
            'applied_at' => $appliedAt,
            'applicant' => $userData,
            'status' => $statusModel,
            'inspector' => $inspectorData,
        ];
        return $data;
    }
}
