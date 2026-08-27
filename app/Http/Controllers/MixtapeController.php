<?php

namespace App\Http\Controllers;

use App\Models\Mixtape;
use App\Support\Locale;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Publieke detailpagina per mixtape — een deelbare URL (social/mail) met eigen
 * OG-meta (cover als afbeelding) en de inline speler. Mixtapes zijn
 * taal-onafhankelijk; alleen de UI-chrome vertaalt mee.
 */
class MixtapeController extends Controller
{
    public function show(Request $request): Response
    {
        $locale = Locale::isSupported($request->route('locale'))
            ? $request->route('locale')
            : Locale::DEFAULT;
        app()->setLocale($locale);

        $mixtape = Mixtape::query()
            ->where('slug', $request->route('slug'))
            ->when(! auth()->check(), fn ($query) => $query->where('published', true))
            ->first();

        if ($mixtape === null) {
            abort(ResponseAlias::HTTP_NOT_FOUND);
        }

        return response()->view('pages.mixtapes.show', [
            'mixtape' => $mixtape,
            'locale' => $locale,
            'seo' => Seo::fromMixtape($mixtape, $locale),
        ]);
    }
}
