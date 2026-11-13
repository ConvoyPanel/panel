<?php

namespace Convoy\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A batch of page views from one browser tab: the body is nothing but
 * `{routePattern: count}`. Nothing in it says who sent it, and nothing is kept
 * about the sender.
 */
class RecordPageViewsRequest extends FormRequest
{
    /** More distinct pages than a tab opens between two sends. */
    public const MAX_PAGES = 50;

    /**
     * The most one batch can add to a page. A tab sends every few minutes and
     * when it is hidden, so this is far above real use, and it keeps a script
     * in a signed-in tab from inflating a page's total.
     */
    public const MAX_VIEWS_PER_PAGE = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*' => ['integer', 'min:1'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $body = $this->json()->all();

                if ($body !== [] && array_is_list($body)) {
                    $validator->errors()->add('views', 'The batch must be an object of page counts.');
                }

                if (count($body) > self::MAX_PAGES) {
                    $validator->errors()->add('views', 'A batch may count at most '.self::MAX_PAGES.' pages.');
                }
            },
        ];
    }

    /** @return array<string, int> route pattern => views, each capped at {@see MAX_VIEWS_PER_PAGE} */
    public function views(): array
    {
        $views = [];

        foreach ($this->json()->all() as $pattern => $count) {
            $views[(string) $pattern] = min((int) $count, self::MAX_VIEWS_PER_PAGE);
        }

        return $views;
    }
}
