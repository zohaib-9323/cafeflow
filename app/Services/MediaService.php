<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsBanner;
use App\Models\Media;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Image uploads for the whole app. Every file is decoded and re-encoded with GD before
 * it is stored. That strips EXIF data (including GPS), neutralises files that are an
 * image and a script at once, and caps the pixel size. Only re-encoded images under
 * random names reach the public disk.
 */
class MediaService
{
    private const FORMATS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** Validation rules to use on any upload field. */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:'.implode(',', config('cafeflow.uploads.mimes')),
            'max:'.config('cafeflow.uploads.max_kb'),
            'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000',
        ];
    }

    public function store(UploadedFile $file, ?User $uploader = null, ?string $alt = null): Media
    {
        $info = @getimagesize($file->getRealPath());
        $mime = $info['mime'] ?? null;
        if (! $info || ! isset(self::FORMATS[$mime])) {
            throw ValidationException::withMessages(['image' => 'Please upload a JPG, PNG or WebP image.']);
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'That image could not be read.']);
        }

        $image = $this->downscale($image, (int) config('cafeflow.uploads.max_dimension'));
        $ext = self::FORMATS[$mime];
        $path = 'media/'.now()->format('Y/m').'/'.Str::random(32).'.'.$ext;

        ob_start();
        match ($ext) {
            'jpg' => imagejpeg($image, null, 84),
            'png' => (imagesavealpha($image, true) && imagepng($image, null, 7)),
            'webp' => imagewebp($image, null, 82),
        };
        $bytes = (string) ob_get_clean();
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        Storage::disk('public')->put($path, $bytes, ['visibility' => 'public']);

        return Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'mime_type' => $mime,
            'size' => strlen($bytes),
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    /** Where (if anywhere) a stored image is still referenced. */
    public function usages(string $path): array
    {
        $uses = [];
        if (ProductImage::query()->where('path', $path)->exists()) {
            $uses[] = 'product images';
        }
        if (Category::withTrashed()->where('image_path', $path)->exists()) {
            $uses[] = 'categories';
        }
        if (CmsBanner::query()->where('image_path', $path)->exists()) {
            $uses[] = 'banners';
        }
        if (Testimonial::query()->where('avatar_path', $path)->exists()) {
            $uses[] = 'testimonials';
        }
        if (SiteSetting::query()->where('value', 'like', '%'.str_replace('/', '\\\\/', $path).'%')->orWhere('value', 'like', "%{$path}%")->exists()) {
            $uses[] = 'homepage content';
        }

        return $uses;
    }

    public function delete(Media $media): void
    {
        if ($uses = $this->usages($media->path)) {
            throw ValidationException::withMessages(['media' => 'This image is still used in: '.implode(', ', $uses).'.']);
        }
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    private function downscale(\GdImage $image, int $max): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if ($w <= $max && $h <= $max) {
            return $image;
        }
        $ratio = min($max / $w, $max / $h);
        $resized = imagescale($image, (int) round($w * $ratio), (int) round($h * $ratio), IMG_BICUBIC);
        imagedestroy($image);

        return $resized;
    }
}
