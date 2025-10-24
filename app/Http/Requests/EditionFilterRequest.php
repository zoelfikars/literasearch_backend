<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "search" => "sometimes|string",
            "page" => "sometimes|integer|min:1",
            "per_page" => "sometimes|integer|min:1|max:50",
            "sort" => "sometimes|string|in:created_at,edition_name,book_name,author_name,publisher_name,publication_year,isbn,rating,stock_available,unfinished_read,wishlisted",
            "order" => "sometimes|string|in:asc,desc"
        ];
    }
}
