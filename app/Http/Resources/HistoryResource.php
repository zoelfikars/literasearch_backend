<?php
namespace App\Http\Resources;
use App\Models\LibrarianApplication;
use App\Models\LibraryApplication;
use App\Models\Loan;
use App\Models\MembershipApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class HistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $iso = fn($dt) => $dt ? $dt->toIso8601String() : null;
        return match (true) {
            $this->resource instanceof Loan => [
                'type' => 'loan',
                'id' => $this->id,
                'title' => optional($this->edition?->title)->title,
                'subtitle' => $this->library?->name,
                'status' => new StatusResource($this->status),
                'created_at' => $iso($this->created_at),
                'inspector' => $this->relationLoaded('inspector')
                    ? (
                        $this->inspector
                        ? [
                            'id' => $this->inspector->id,
                            'name' => $this->inspector->nickname,
                        ]
                        : null
                    )
                    : null,
                'meta' => [
                    'due_date' => $iso($this->due_date),
                    'returned_at' => $iso($this->returned_at),
                    'library' => $this->library ? ['id' => $this->library->id, 'name' => $this->library->name] : null,
                    'inspected_at' => $iso($this->inspected_at),
                ],
            ],
            $this->resource instanceof LibraryApplication => [
                'type' => 'library_application',
                'id' => $this->id,
                'title' => $this->library?->name,
                'subtitle' => 'Pengajuan perpustakaan',
                'status' => new StatusResource($this->status),
                'created_at' => $iso($this->created_at),
                'inspector' => $this->relationLoaded('inspector')
                    ? (
                        $this->inspector
                        ? [
                            'id' => $this->inspector->id,
                            'name' => $this->inspector->nickname,
                        ]
                        : null
                    )
                    : null,
                'meta' => [
                    'expiration_date' => $iso($this->expiration_date),
                    'inspected_at' => $iso($this->inspected_at),
                ],
            ],
            $this->resource instanceof LibrarianApplication => [
                'type' => 'librarian_application',
                'id' => $this->id,
                'title' => $this->library?->name,
                'subtitle' => 'Pengajuan pustakawan',
                'status' => new StatusResource($this->status),
                'created_at' => $iso($this->created_at),
                'inspector' => $this->relationLoaded('inspector')
                    ? (
                        $this->inspector
                        ? [
                            'id' => $this->inspector->id,
                            'name' => $this->inspector->nickname,
                        ]
                        : null
                    )
                    : null,
                'meta' => [
                    'inspected_at' => $iso($this->inspected_at),
                ],
            ],
            $this->resource instanceof MembershipApplication => [
                'type' => 'membership_application',
                'id' => $this->id,
                'title' => $this->user?->nickname,
                'subtitle' => $this->library?->name,
                'status' => new StatusResource($this->status),
                'created_at' => $iso($this->created_at),
                'inspector' => $this->relationLoaded('inspector')
                    ? (
                        $this->inspector
                        ? [
                            'id' => $this->inspector->id,
                            'name' => $this->inspector->nickname,
                        ]
                        : null
                    )
                    : null,
                'meta' => [
                    'inspected_at' => $iso($this->inspected_at),
                ],
            ],
            default => [
                'type' => 'unknown',
                'id' => (string) data_get($this, 'id'),
                'title' => null,
                'subtitle' => null,
                'status' => null,
                'created_at' => null,
                'meta' => null,
            ],
        };
    }
}
