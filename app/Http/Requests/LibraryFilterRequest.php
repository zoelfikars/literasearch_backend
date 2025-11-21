<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class LibraryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'sort' => ['sometimes', 'in:id,name,created_at,rating,rating_count,distance,stock,inspection,is_librarian,road_distance'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'max_candidates' => ['sometimes', 'integer', 'min:1', 'max:625'],
            'edition_id' => ['sometimes', 'uuid', 'exists:editions,id'],
        ];
    }
}
