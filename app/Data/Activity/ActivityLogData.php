<?php

namespace App\Data\Activity;

use App\Data\User\UserData;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ActivityLogData extends Data
{
    public function __construct(
        public int $id,
        public ?string $batch,
        public string $event,
        public ?string $ip,
        public string $description,
        public array $properties,
        public CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        #[LoadRelation]
        public Lazy|UserData|null $actor,
    ) {}

    public static function fromModel(ActivityLog $model, ?Request $request = null): self
    {
        $request ??= request();
        $canViewIp = $model->actor?->is($request->user())
            || (bool) $request->user()?->root_admin;

        return new self(
            id: $model->id,
            batch: $model->batch,
            event: $model->event,
            ip: $canViewIp ? $model->ip : null,
            description: $model->description ?? '',
            properties: self::normalizeProperties($model, $request),
            createdAt: CarbonImmutable::parse($model->created_at),
            updatedAt: $model->updated_at
                ? CarbonImmutable::parse($model->updated_at)
                : null,
            actor: Lazy::whenLoaded(
                'actor',
                $model,
                fn () => $model->actor instanceof User
                    ? UserData::from($model->actor)
                    : null,
            ),
        );
    }

    private static function normalizeProperties(ActivityLog $model, Request $request): array
    {
        if ($model->properties->isEmpty()) {
            return [];
        }

        $properties = $model->properties
            ->mapWithKeys(function ($value, $key) use ($model, $request) {
                if ($key === 'ip' && ! $model->actor?->is($request->user())) {
                    return [$key => '[hidden]'];
                }

                if (! is_array($value)) {
                    if ($key === 'directory') {
                        $value = str_replace('//', '/', '/'.trim($value, '/').'/');
                    }

                    return [$key => $value];
                }

                return [$key => $value, "{$key}_count" => count($value)];
            });

        $keys = $properties->keys()
            ->filter(fn ($key) => str_ends_with($key, '_count'))
            ->values();

        if ($keys->containsOneItem()) {
            $properties = $properties
                ->merge(['count' => $properties->get($keys[0])])
                ->except($keys[0]);
        }

        return $properties->toArray();
    }
}
