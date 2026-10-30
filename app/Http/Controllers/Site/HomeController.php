<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CmsBanner;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'featured' => Product::query()->listed()->where('is_featured', true)
                ->with(['primaryImage', 'category'])->orderBy('sort_order')->limit(8)->get(),
            'categories' => Category::query()->active()
                ->whereHas('products', fn ($q) => $q->listed())
                ->withCount(['products' => fn ($q) => $q->listed()])->limit(6)->get(),
            'banners' => CmsBanner::query()->live('home')->limit(3)->get(),
            'offers' => Promotion::query()->currentlyValid()->where('show_on_site', true)->latest()->limit(3)->get(),
            'testimonials' => Testimonial::query()->published()->limit(6)->get(),
        ]);
    }
}
