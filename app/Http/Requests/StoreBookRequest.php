<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'isbn_10' => 'nullable|string|min:10|max:10',
            'isbn_13' => 'required_without:isbn_10|string|min:13|max:13',
            'edition_number' => 'required|integer|min:1',
            'publication_date' => 'required|date',
            'cover' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'file' => 'nullable|mimes:pdf|max:204800',
            'pages' => 'required|integer|min:1',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',

            'title_id' => 'required|exists:book_titles,id|uuid',
            'language_id' => 'required|exists:languages,id|uuid',
            'publisher_id' => 'required|uuid|exists:publishers,id',

            'subject_ids' => 'required|array',
            'subject_ids.*' => 'uuid|exists:subjects,id',
            'contributors' => ['required', 'array', 'min:1'],
            'contributors.*.author_id' => ['required', 'uuid', 'exists:authors,id'],
            'contributors.*.role_id' => ['required', 'uuid', 'exists:author_roles,id'],
        ];
    }
}
