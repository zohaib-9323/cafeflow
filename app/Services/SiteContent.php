<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** CMS-managed single-instance content (hero, about, contact, hours, business settings). */
class SiteContent
{
    private const CACHE_KEY = 'site_settings.v1';

    public const DEFAULTS = [
        'business' => [
            'name' => 'Larkspur Coffee House',
            'tagline' => 'Slow coffee, honest food.',
        ],
        'hero' => [
            'eyebrow' => 'Specialty coffee · Kitchen · Bakery',
            'heading' => 'Coffee worth slowing down for.',
            'description' => 'Single-origin espresso, pastries laminated in-house every morning and a seasonal kitchen menu. Order ahead for pickup or delivery.',
            'image' => null,
            'cta_label' => 'Order now',
            'cta_url' => '/menu',
            'secondary_label' => 'Our story',
            'secondary_url' => '/about',
        ],
        'about' => [
            'heading' => 'A neighbourhood coffee house',
            'body' => 'We roast in small batches, bake before sunrise and cook with whatever the market brings in that week.',
            'image' => null,
        ],
        'home' => [
            'featured_heading' => "This week's favourites",
            'categories_heading' => 'Explore the menu',
            'testimonials_heading' => 'Kind words from regulars',
        ],
        'contact' => [
            'phone' => '(555) 014-2290',
            'email' => 'hello@larkspur.test',
            'address' => '42 Mill Lane',
            'city' => 'Riverton, 10014',
            'map_url' => null,
        ],
        'hours' => [
            ['days' => 'Monday – Friday', 'hours' => '7:00 – 19:00'],
            ['days' => 'Saturday', 'hours' => '8:00 – 19:00'],
            ['days' => 'Sunday', 'hours' => '8:00 – 16:00'],
        ],
    ];

    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => SiteSetting::query()->pluck('value', 'key')->all());

        $merged = self::DEFAULTS;
        foreach ($stored as $key => $value) {
            $merged[$key] = is_array($value) && Arr::isAssoc($value) && isset(self::DEFAULTS[$key]) && Arr::isAssoc(self::DEFAULTS[$key])
                ? array_merge(self::DEFAULTS[$key], $value)
                : $value;
        }

        return $merged;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function put(string $key, mixed $value): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public function imageUrl(?string $path, string $fallback): string
    {
        return $path ? Storage::disk('public')->url($path) : asset($fallback);
    }
}
