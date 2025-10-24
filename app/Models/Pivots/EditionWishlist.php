<?php
namespace App\Models\Pivots;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
class EditionWishlist extends Pivot
{
    protected $table = 'edition_wishlists';
    protected $primaryKey = ['edition_id', 'user_id'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'edition_id',
        'user_id',
    ];
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
