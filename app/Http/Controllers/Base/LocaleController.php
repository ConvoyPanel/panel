<?php

/*
Pterodactyl®
Copyright © Dane Everitt <dane@daneeveritt.com> and contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Translation\Translator;

class LocaleController extends Controller
{
    protected Loader $loader;

    public function __construct(Translator $translator)
    {
        $this->loader = $translator->getLoader();
    }

    /**
     * Returns translation data given a specific locale and namespace.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $locales = explode(' ', $request->input('locale') ?? '');
        $namespaces = explode(' ', $request->input('namespace') ?? '');

        $response = [];
        foreach ($locales as $locale) {
            $response[$locale] = [];
            foreach ($namespaces as $namespace) {
                $response[$locale][$namespace] = $this->i18n(
                    $this->loader->load($locale, str_replace('.', '/', $namespace)),
                );
            }
        }

        return new JsonResponse($response, 200, [
            // Cache this in the browser for an hour, and allow the browser to use a stale
            // cache for up to a day after it was created while it fetches an updated set
            // of translation keys.
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'ETag' => md5(json_encode($response, JSON_THROW_ON_ERROR)),
        ]);
    }

    /**
     * Convert standard Laravel translation keys that look like ":foo"
     * into key structures that are supported by the front-end i18n
     * library, like "{{foo}}".
     */
    protected function i18n(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->i18n($value);
            } else {
                // Find a Laravel style translation replacement in the string and replace it with
                // one that the front-end is able to use. This won't always be present, especially
                // for complex strings or things where we'd never have a backend component anyways.
                //
                // For example:
                // "Hello :name, the :notifications.0.title notification needs :count actions :foo.0.bar."
                //
                // Becomes:
                // "Hello {{name}}, the {{notifications.0.title}} notification needs {{count}} actions {{foo.0.bar}}."
                $data[$key] = preg_replace('/:([\w.-]+\w)([^\w:]?|$)/m', '{{$1}}$2', $value);
            }
        }

        return $data;
    }
}
