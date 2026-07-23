<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Locale;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PublicPageController extends Controller
{
    public function show(Request $request): Response
    {
        // Locale uit de route-prefix (en/es); anders de standaardtaal (nl).
        $locale = Locale::isSupported($request->route('locale'))
            ? $request->route('locale')
            : Locale::DEFAULT;
        app()->setLocale($locale);

        $slug = $request->route('slug'); // null = homepage
        $previewDrafts = auth()->check();

        $page = $slug === null
            ? Page::query()
                ->where('locale', $locale)
                ->where('is_homepage', true)
                ->with('sections')
                ->first()
            : Page::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when(! $previewDrafts, fn ($query) => $query->where('published', true))
                ->with('sections')
                ->first();

        if ($page !== null) {
            return response()->view('pages.show', [
                'page' => $page,
                'seo' => Seo::fromPage($page),
            ]);
        }

        if ($slug !== null && view()->exists("pages.previews.{$slug}")) {
            return response()->view("pages.previews.{$slug}");
        }

        abort(ResponseAlias::HTTP_NOT_FOUND);
    }
}
