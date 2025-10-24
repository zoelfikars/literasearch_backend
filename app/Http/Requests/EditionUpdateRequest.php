<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'isbn_10' => 'nullable|string|min:10|max:10',
            'isbn_13' => 'nullable|string|min:13|max:13',
            'edition_number' => 'sometimes|integer|min:1',
            'publication_year' => 'sometimes|digits:4|integer|min:1000|max:' . date('Y'),
            'cover' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'file' => 'nullable|mimes:pdf|max:204800',
            'pages' => 'sometimes|integer|min:1',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',

            'title_id' => 'sometimes|exists:book_titles,id|uuid',
            'language_id' => 'sometimes|exists:languages,id|uuid',
            'publisher_id' => 'sometimes|uuid|exists:publishers,id',

            'subject_ids' => 'sometimes|array',
            'subject_ids.*' => 'uuid|exists:subjects,id',
            'contributors' => ['sometimes', 'array', 'min:1'],
            'contributors.*.author_id' => ['required_with:contributors', 'uuid', 'exists:authors,id'],
            'contributors.*.role_id' => ['required_with:contributors', 'uuid', 'exists:author_roles,id'],
        ];
    }
}
