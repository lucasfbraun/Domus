<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Concerns\RegistersOptimizedWebpConversions;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'address', 'type', 'status'])]
class Property extends Model implements HasMedia
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use RegistersOptimizedWebpConversions;

    public const COVER_COLLECTION = 'cover';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'status' => PropertyStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COVER_COLLECTION)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerOptimizedWebpConversion();
    }

    public function coverUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::COVER_COLLECTION, 'webp');

        if ($url !== '') {
            return $url;
        }

        $original = $this->getFirstMediaUrl(self::COVER_COLLECTION);

        return $original !== '' ? $original : null;
    }

    /**
     * @return BelongsToMany<Owner, $this>
     */
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
