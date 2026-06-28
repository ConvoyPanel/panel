<?php

namespace App\Data\Server\Templates;

use App\Models\Template;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class TemplateData extends Data
{
    public function __construct(
        public string $uuid,
        public int $templateGroupId,
        public string $name,
        public ?string $description,
        public int $vmid,
        public bool $isAdminOnly,
    ) {}

    public static function fromModel(Template $template): self
    {
        return new self(
            uuid: $template->uuid,
            templateGroupId: $template->template_group_id,
            name: $template->name,
            description: $template->description,
            vmid: $template->vmid,
            isAdminOnly: (bool) $template->is_admin_only,
        );
    }
}
