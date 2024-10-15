<?php

namespace App\Transformers\Client;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class ActivityLogTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['actor'];

    protected Request $request;

    public function __construct()
    {
        Container::getInstance()->call([$this, 'loadDependencies']);
    }

    public function loadDependencies(Request $request)
    {
        $this->request = $request;
    }

    public function transform(ActivityLog $model): array
    {
        return [
            // This is not for security, it is only to provide a unique identifier to
            // the front-end for each entry to improve rendering performance since there
            // is nothing else sufficiently unique to key off at this point.
            'id' => $model->id,
            'batch' => $model->batch,
            'event' => $model->event,
            'ip' => $this->canViewIP($model->actor) ? $model->ip : null,
            'description' => $model->description,
            'properties' => $this->properties($model),
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ];
    }

    public function includeActor(ActivityLog $model)
    {
        if (! $model->actor instanceof User) {
            return $this->null();
        }

        return $this->item($model->actor, new UserTransformer);
    }

    /**
     * Transforms any array values in the properties into a countable field for easier
     * use within the translation outputs.
     */
    protected function properties(ActivityLog $model): array
    {
        if (! $model->properties || $model->properties->isEmpty()) {
            return [];
        }

        $properties = $model->properties
            ->mapWithKeys(function ($value, $key) use ($model) {
                if ($key === 'ip' && ! $model->actor?->is($this->request->user())) {
                    return [$key => '[hidden]'];
                }

                if (! is_array($value)) {
                    // Perform some directory normalization at this point.
                    if ($key === 'directory') {
                        $value = str_replace('//', '/', '/'.trim($value, '/').'/');
                    }

                    return [$key => $value];
                }

                return [$key => $value, "{$key}_count" => count($value)];
            });

        $keys = $properties->keys()->filter(fn ($key) => Str::endsWith($key, '_count'))->values();
        if ($keys->containsOneItem()) {
            $properties = $properties->merge(['count' => $properties->get($keys[0])])->except($keys[0]);
        }

        return $properties->toArray();
    }

    /**
     * Determines if there are any log properties that we've not already exposed
     * in the response language string and that are not just the IP address or
     * the browser useragent.
     *
     * This is used by the front-end to selectively display an "additional metadata"
     * button that is pointless if there is nothing the user can't already see from
     * the event description.
     */
    protected function hasAdditionalMetadata(ActivityLog $model): bool
    {
        if (is_null($model->properties) || $model->properties->isEmpty()) {
            return false;
        }

        $str = trans('activity.'.str_replace(':', '.', $model->event));
        preg_match_all('/:(?<key>[\w.-]+\w)(?:[^\w:]?|$)/', $str, $matches);

        $exclude = array_merge($matches['key'], ['ip', 'useragent', 'using_sftp']);
        foreach ($model->properties->keys() as $key) {
            if (! in_array($key, $exclude, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the user can view the IP address in the output either because they are the
     * actor that performed the action, or because they are an administrator on the Panel.
     */
    protected function canViewIP(Model $actor = null): bool
    {
        return $actor?->is($this->request->user()) || $this->request->user()?->root_admin;
    }
}
