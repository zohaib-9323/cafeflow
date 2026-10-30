<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = ['disk', 'path', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt', 'uploaded_by'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576 ? round($this->size / 1048576, 1).' MB' : max(1, round($this->size / 1024)).' KB';
    }
}
