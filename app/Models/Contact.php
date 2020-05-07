<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Class Contact
 * @package App\Models
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $email
 * @property string $phone
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Contact extends Model
{
    public $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        // Automatically generate a new UUID on model creation
        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
