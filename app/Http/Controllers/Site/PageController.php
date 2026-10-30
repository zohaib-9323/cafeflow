<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = CmsPage::query()->published()->where('slug', $slug)->firstOrFail();

        return view('site.page', ['page' => $page]);
    }
}
