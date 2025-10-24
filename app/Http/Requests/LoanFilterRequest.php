<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'sort' => [
                'sometimes',
                'string',
                'in:created_at,book_name,borrower_name,loaned_at,due_date,returned_at,pending,not_returned,returned,overdue',
            ],
            'order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search' => 'sometimes|string|max:255',
        ];
    }
}
