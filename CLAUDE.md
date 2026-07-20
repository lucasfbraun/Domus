# Project agent instructions (Claude)

## Brand colors (do not revert)

Primary is blue; surfaces are white / neutral gray. Never restore the old teal/green brand palette.

- `--primary`: `hsl(221 83% 53%)` (keep `--ring`, `--sidebar-primary`, `--chart-1` in sync)
- `--background` / `--sidebar-background`: `hsl(0 0% 100%)`
- Neutrals use `hsl(0 0% …)` only (no green or blue-tinted page wash)
- Forbidden brand hues ~150–175 (e.g. `hsl(175 72% 28%)`, `hsl(160 18% 97%)`)
- Green is allowed only for semantic success (messages, status badges), not brand chrome
- See `.cursor/rules/brand-colors.mdc` and `tests/Feature/ThemeColorsTest.php`

## Spatie Media Library (required for media)

All image/media uploads MUST use `spatie/laravel-medialibrary`. Do not store images via raw `Storage::put`, `$file->store()`, or custom path columns when the file is media/image content.

### Required model setup

1. Implement `Spatie\MediaLibrary\HasMedia`
2. Use `Spatie\MediaLibrary\InteractsWithMedia`
3. Use `App\Models\Concerns\RegistersOptimizedWebpConversions`
4. Call `$this->registerOptimizedWebpConversion()` inside `registerMediaConversions()`

```php
use App\Models\Concerns\RegistersOptimizedWebpConversions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Property extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersOptimizedWebpConversions;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerOptimizedWebpConversion();
    }
}
```

### Required conversion

Every media-capable model must register a WebP conversion with optimization:

```php
$this->addMediaConversion('webp')
    ->format('webp')
    ->optimize();
```

Extra conversions (thumb, cover, etc.) are allowed, but each image conversion must also use `->format('webp')->optimize()` unless there is a documented exception (e.g. SVG icons, non-image files).

### Adding / reading media

```php
$model->addMediaFromRequest('photo')->toMediaCollection('photos');
$url = $model->getFirstMediaUrl('photos', 'webp');
```

### Exceptions

Non-image documents (PDF contracts, invoices) may still use filesystem storage when they are not Media Library collections. Prefer Media Library when the file is an image or belongs to a media gallery.

Also follow Cursor rules in `.cursor/rules/` and `AGENTS.md`.
