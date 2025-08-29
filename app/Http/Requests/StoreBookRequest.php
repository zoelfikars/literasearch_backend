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
            'file' => 'required|nullable|mimes:pdf|max:10240',
            'pages' => 'required|integer|min:1',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',

            'title_id' => 'required|exists:titles,id|uuid',
            'language_id' => 'required|exists:languages,id|uuid',
            'publisher_id' => 'required|uuid|exists:publishers,id',

            'author_ids' => 'required|array',
            'author_ids.*' => 'uuid|exists:authors,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'uuid|exists:subjects,id',
        ];
    }
}
