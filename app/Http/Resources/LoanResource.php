<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loaned_at' => optional($this->loaned_at)->toIso8601String(),
            'due_date' => optional($this->due_date)->toIso8601String(),
            'returned_at' => optional($this->returned_at)->toIso8601String(),
            'notes' => $this->notes,
            'rejection_reason' => $this->status && $this->status->name === 'rejected' ? $this->rejection_reason : null,
            'book' => $this->relationLoaded('edition') && $this->edition != null ? [
                'id' => $this->edition->id,
                'title' => $this->relationLoaded('edition') && $this->edition->title != null ? $this->edition->title->title : null,
                'subtitle' => $this->edition->subtitle,
                'isbn' => $this->edition->isbn_13 ?? $this->edition->isbn_10,
            ] : null,
            'borrower' => $this->relationLoaded('borrower') && $this->borrower != null ? [
                'id' => $this->borrower->id,
                'nickname' => $this->borrower->nickname,
                'full_name' => $this->borrower->identity != null ? $this->borrower->identity->full_name : null,
            ] : null,
            'inspector' => $this->relationLoaded('inspector') && $this->inspector != null ? [
                'id' => $this->inspector->id,
                'nickname' => $this->inspector->nickname,
                'inspected_at' => optional($this->inspected_at)->toIso8601String(),
            ] : null,
            'status' => $this->relationLoaded('status') && $this->status != null ? StatusResource::make($this->status) : null,
        ];
    }
}
