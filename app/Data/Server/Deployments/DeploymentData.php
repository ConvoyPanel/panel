<?php

namespace App\Data\Server\Deployments;

use App\Data\Image\ImageDefinitionData;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Models\Deployment;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DeploymentData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public ?int $imageDefinitionId,
        public ?int $imageVersionId,
        public DeploymentStatus $status,
        public DeploymentType $type,
        public bool $startOnCompletion,
        public CarbonImmutable $requestedAt,
        public ?CarbonImmutable $completedAt,
        #[LoadRelation]
        public Lazy|ImageDefinitionData|null $image,
        #[LoadRelation]
        /** @var Lazy|DataCollection<int, DeploymentStepData> */
        public Lazy|DataCollection $steps,
    ) {}

    public static function fromModel(Deployment $deployment): self
    {
        return new self(
            id: $deployment->id,
            serverId: $deployment->server_id,
            imageDefinitionId: $deployment->image_definition_id,
            imageVersionId: $deployment->image_version_id,
            status: $deployment->status,
            type: $deployment->type,
            startOnCompletion: (bool) $deployment->start_on_completion,
            requestedAt: CarbonImmutable::parse($deployment->requested_at),
            completedAt: $deployment->completed_at
                ? CarbonImmutable::parse($deployment->completed_at)
                : null,
            image: Lazy::whenLoaded(
                'imageDefinition',
                $deployment,
                fn () => $deployment->imageDefinition
                    ? ImageDefinitionData::from($deployment->imageDefinition)
                    : null,
            ),
            steps: Lazy::whenLoaded(
                'steps',
                $deployment,
                fn () => DeploymentStepData::collect(
                    $deployment->steps,
                    DataCollection::class,
                ),
            ),
        );
    }
}
