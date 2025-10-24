<?php
namespace App\Rules;
use App\Models\AuthorRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
class AtLeastOneContributorRole implements ValidationRule
{
    public function __construct(
        protected string $slug = 'penulis'
    ) {
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Format kontributor tidak valid.');
            return;
        }
        $roleId = AuthorRole::query()->where('slug', $this->slug)->value('id');
        if (!$roleId) {
            $fail("Peran dengan slug '{$this->slug}' belum terdaftar.");
            return;
        }
        $found = collect($value)->contains(function ($item) use ($roleId) {
            if (data_get($item, 'role_id') === $roleId) {
                return true;
            }
            if (data_get($item, 'role_slug') === $this->slug) {
                return true;
            }
            return false;
        });
        if (!$found) {
            $fail("Setidaknya satu kontributor harus berperan sebagai {$this->slug}.");
        }
    }
}
