<?php

namespace Database\Seeders;

use App\Enums\User\UserType;
use App\Models\AdminRole;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\PermissionSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Puts the database into the state the Playwright suite (`tests/e2e`) starts from.
 *
 * Run by the suite's global setup before every run, so it resets rather than
 * adds: the three accounts get their password, type and role back, guest
 * accounts are switched off, and whatever a previous run imported or uploaded
 * is deleted. The account details are mirrored in `tests/e2e/support/users.ts`.
 *
 *   ddev artisan db:seed --class=E2eSeeder
 */
class E2eSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const SUPERADMIN_EMAIL = 'admin@e2e.test';

    public const USER_EMAIL = 'user@e2e.test';

    public const GUEST_EMAIL = 'guest@e2e.test';

    /** Roles a run creates are named with this prefix, so the next run can find them. */
    public const ROLE_PREFIX = 'E2E ';

    public const UPLOAD_GROUP = 'E2E Uploads';

    public const UPLOAD_IMAGE = 'E2E Upload Target';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('E2eSeeder resets accounts and images. Refusing to run in production.');

            return;
        }

        $this->seedAccounts();
        $this->resetSettings();
        $this->resetRoles();
        $this->resetImages();

        $this->command->info('Seeded the e2e accounts and fixtures.');
    }

    private function seedAccounts(): void
    {
        $this->account(self::SUPERADMIN_EMAIL, 'E2E Superadmin', [
            'type' => UserType::STANDARD,
            'admin_role_id' => AdminRole::superadmin()->id,
        ]);

        $this->account(self::USER_EMAIL, 'E2E User', [
            'type' => UserType::STANDARD,
            'admin_role_id' => null,
        ]);

        $this->account(self::GUEST_EMAIL, 'E2E Guest', [
            'type' => UserType::GUEST,
            'admin_role_id' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function account(string $email, string $name, array $attributes): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            ...$attributes,
        ])->save();
    }

    private function resetSettings(): void
    {
        $settings = app(PermissionSettings::class);
        $settings->allow_guest_accounts = false;
        $settings->save();
    }

    private function resetRoles(): void
    {
        // users.admin_role_id is nullOnDelete, so anyone left on one of these drops back to no role.
        AdminRole::query()
            ->where('is_system', false)
            ->where('name', 'like', self::ROLE_PREFIX.'%')
            ->delete();
    }

    private function resetImages(): void
    {
        $catalogue = json_decode(
            (string) file_get_contents(base_path('tests/e2e/fixtures/registry.json')),
            true,
        );

        $groups = collect($catalogue['groups'] ?? []);

        // Definitions and versions cascade with their group. An import adopts a group
        // by name as well as by slug, so both are cleared.
        ImageGroup::query()
            ->whereIn('registry_slug', $groups->pluck('id'))
            ->orWhereIn('name', $groups->pluck('display_name')->push(self::UPLOAD_GROUP))
            ->get()
            ->each->delete();

        ImageUpload::query()
            ->whereIn('user_id', User::query()->where('email', self::SUPERADMIN_EMAIL)->select('id'))
            ->get()
            ->each->delete();

        $group = ImageGroup::query()->create([
            'name' => self::UPLOAD_GROUP,
            'is_admin_only' => false,
        ]);

        ImageDefinition::query()->create([
            'image_group_id' => $group->id,
            'name' => self::UPLOAD_IMAGE,
            'ostype' => 'l26',
            'hardware' => [],
            'is_admin_only' => false,
        ]);

        // The catalogue is cached by URL; drop it so an edited fixture is read fresh.
        Cache::forget('images.registry.'.sha1((string) config('convoy.registry.url')));
    }
}
