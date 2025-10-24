<?php
namespace App\Http\Requests;
use App\Models\AuthorRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class EditionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $edition = $this->route('edition');
        $editionId = $edition?->id ?? null;
        return [
            'isbn_10' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:10',
                Rule::unique('editions', 'isbn_10')->ignore($editionId),
            ],
            'isbn_13' => [
                'sometimes',
                'string',
                'min:13',
                'max:13',
                Rule::unique('editions', 'isbn_13')->ignore($editionId),
            ],
            'edition_number' => ['sometimes', 'integer', 'min:1'],
            'publication_year' => ['sometimes', 'digits:4', 'integer', 'min:1000', 'max:' . date('Y')],
            'cover' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'file' => ['sometimes', 'nullable', 'mimes:pdf,epub,mobi', 'max:204800'],
            'pages' => ['sometimes', 'integer', 'min:1'],
            'subtitle' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'title_id' => ['sometimes', 'uuid', 'exists:book_titles,id'],
            'language_id' => ['sometimes', 'uuid', 'exists:languages,id'],
            'publisher_id' => ['sometimes', 'uuid', 'exists:publishers,id'],
            'subject_ids' => ['sometimes', 'array'],
            'subject_ids.*' => ['uuid', 'exists:subjects,id'],
            'contributors' => ['sometimes', 'array', 'min:1'],
            'contributors.*.author_id' => ['required_with:contributors', 'uuid', 'exists:authors,id'],
            'contributors.*.role_id' => ['required_with:contributors', 'uuid', 'exists:author_roles,id'],
            'contributors.*.role_slug' => ['sometimes', 'string'],
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\Edition $edition */
            $edition = $this->route('edition');
            $contributors = $this->input('contributors', null);
            $slug = 'penulis';
            $roleId = AuthorRole::where('slug', $slug)->value('id');
            if (!$roleId) {
                $validator->errors()->add('contributors', "Peran dengan slug '{$slug}' belum terdaftar.");
                return;
            }
            if (is_array($contributors)) {
                $hasPenulisInPayload = collect($contributors)->contains(function ($c) use ($roleId, $slug) {
                    return data_get($c, 'role_id') === $roleId
                        || data_get($c, 'role_slug') === $slug;
                });
                if (!$hasPenulisInPayload) {
                    $validator->errors()->add('contributors', 'Minimal satu kontributor harus berperan sebagai penulis.');
                }
            } else {
                if ($edition) {
                    $hasPenulisInDB = $edition->contributors()
                        ->wherePivot('role_id', $roleId)
                        ->exists();
                    if (!$hasPenulisInDB) {
                        $validator->errors()->add('contributors', 'Edisi ini belum memiliki kontributor berperan penulis.');
                    }
                }
            }
        });
    }
}
