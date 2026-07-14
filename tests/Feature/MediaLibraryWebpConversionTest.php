<?php

use App\Models\Concerns\RegistersOptimizedWebpConversions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');

    Schema::create('media_test_models', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('media_test_models');
});

test('media library stores an optimized webp conversion', function () {
    $model = MediaTestModel::query()->create();

    $model
        ->addMedia(UploadedFile::fake()->image('photo.jpg', 200, 200))
        ->toMediaCollection('photos');

    $media = $model->getFirstMedia('photos');

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('webp'))->toBeTrue();

    Storage::disk('public')->assertExists($media->getPathRelativeToRoot('webp'));
});

class MediaTestModel extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersOptimizedWebpConversions;

    protected $table = 'media_test_models';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerOptimizedWebpConversion();
    }
}
