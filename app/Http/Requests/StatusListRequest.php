<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "type"=> "required|string|in:user,library_application,membership_application,loan,librarian_application",
        ];
    }
}
