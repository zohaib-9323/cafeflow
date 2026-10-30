<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'body', 'seo_title', 'seo_description', 'is_published', 'show_in_footer'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'show_in_footer' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Markdown to HTML with raw HTML stripped and unsafe links dropped, so admin content can't inject script. */
    public function renderedBody(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
