<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'locale',
        'key',
        'content',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'translation_tag');
    }

    protected static function booted(): void
    {
        static::saved(fn (self $t) => Cache::forget(self::cacheKey($t->locale)));
        static::deleted(fn (self $t) => Cache::forget(self::cacheKey($t->locale)));
    }

    public static function cacheKey(string $locale): string
    {
        return "translations:export:{$locale}";
    }
}
