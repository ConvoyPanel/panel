<?php

namespace App\Console\Commands\Api;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\PersonalAccessToken;
use App\Models\SystemActor;
use App\Rules\IpAddressOrCidr;
use App\Services\Api\CreateApplicationTokenService;
use App\Support\Api\TokenAbilities;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Mints an application token without the panel.
 *
 * The panel only issues tokens from a signed-in session with a confirmed identity, which leaves
 * a fresh install driven by a provisioning tool with no way to get its first credential. Whoever
 * can run artisan on the host already holds everything a token would grant, so this needs no
 * further check.
 */
class CreateApplicationTokenCommand extends Command
{
    protected $description = 'Create an application API token.';

    protected $signature = 'tokens:create
        {--name= : A name to recognise the token by}
        {--ability=* : An ability such as servers:write, repeatable. Omit for full access}
        {--allow=* : An IP address or CIDR the token may be used from, repeatable. Omit for anywhere}
        {--expires= : When the token stops working: a date, or a relative time such as "+90 days"}
        {--plain : Print only the token, for scripts}';

    protected $help = <<<'TEXT'
        Examples:
          php artisan tokens:create --name=terraform --plain
          php artisan tokens:create --name=whmcs --ability=servers:* --ability=users:* --allow=203.0.113.10
          php artisan tokens:create --name=ci --ability=servers:read --expires="+90 days"
        TEXT;

    public function handle(CreateApplicationTokenService $service): int
    {
        // Parsed here rather than by the `date` rule, which refuses relative times.
        $expires = $this->option('expires');

        try {
            $expiresAt = blank($expires) ? null : CarbonImmutable::parse($expires);
        } catch (InvalidFormatException) {
            $this->components->error("--expires is not a date or a relative time: {$expires}");

            return self::FAILURE;
        }

        $input = [
            'name' => $this->option('name'),
            'abilities' => $this->option('ability') ?: ['*'],
            'allowed_networks' => $this->option('allow'),
            'expires_at' => $expiresAt?->toIso8601String(),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'between:1,191'],
            'abilities' => ['array'],
            'abilities.*' => ['string', Rule::in(TokenAbilities::all())],
            'allowed_networks' => ['array', 'max:100'],
            'allowed_networks.*' => ['string', 'max:191', new IpAddressOrCidr],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'name.required' => 'Give the token a name with --name.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $token = $service->handle(
            creator: null,
            name: $input['name'],
            abilities: array_values($input['abilities']),
            allowedNetworks: array_values(array_unique(array_map(trim(...), $input['allowed_networks']))),
            expiresAt: $expiresAt,
        );

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        Audit::record(
            AuditEvent::ADMIN_TOKEN_CREATED,
            subject: $accessToken,
            properties: [
                'name' => $accessToken->name,
                'abilities' => $accessToken->abilities,
                'allowed_networks' => $accessToken->allowed_networks,
                'expires_at' => $accessToken->expires_at?->toIso8601String(),
                'via' => 'cli',
            ],
            actor: SystemActor::instance(),
        );

        if ($this->option('plain')) {
            $this->line($token->plainTextToken);

            return self::SUCCESS;
        }

        $this->components->info("Created token {$accessToken->name}. It is shown once.");
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
