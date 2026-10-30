<?php

namespace App\View\Composers;

use App\Models\CmsPage;
use App\Services\Cart\CartService;
use App\Services\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SiteComposer
{
    public function __construct(private readonly SiteContent $content, private readonly CartService $cart) {}

    public function compose(View $view): void
    {
        $view->with([
            'site' => $this->content->all(),
            'cartCount' => $this->cart->count(),
            // Plain arrays: the cache store refuses to unserialize objects.
            'footerPages' => Cache::remember('footer_pages', 600, fn () => CmsPage::query()
                ->published()->where('show_in_footer', true)->orderBy('title')->get(['title', 'slug'])->toArray()),
        ]);
    }
}
