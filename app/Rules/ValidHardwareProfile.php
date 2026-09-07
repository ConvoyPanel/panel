<?php

namespace App\Rules;

use App\Models\Node;
use App\Services\Anchor\AnchorSchemaService;
use App\Services\Images\OsProfiles;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Checks a hardware overlay against Proxmox's own parameter schema.
 *
 * The point is to move the failure from first power-on to submit. A wrong
 * `scsihw` is not a config that merely looks odd -- it is a guest that cannot
 * find its root disk, which surfaces minutes later as `0x7B` on Windows or an
 * initramfs prompt on Linux, long after whoever typed it has moved on.
 *
 * Two kinds of rejection, and the second matters as much as the first:
 * a key Proxmox does not accept, and a key the panel composes itself. Letting
 * an admin set `vmid` or `scsi0` by hand would mean an overlay silently
 * overwriting the disk the image is being imported into.
 */
class ValidHardwareProfile implements ValidationRule
{
    /**
     * Slots and identity the build owns. Written by the create call from the
     * server's own plan, so an overlay naming them is always a mistake.
     */
    private const COMPUTED_PATTERN = '/^(?:scsi|ide|sata|virtio|net|efidisk|tpmstate|unused|ipconfig)\d+$/';

    private const COMPUTED_KEYS = [
        'vmid', 'cores', 'memory', 'sockets', 'name', 'ostype',
        'citype', 'ciuser', 'cipassword', 'cicustom', 'sshkeys',
        'nameserver', 'searchdomain', 'archive', 'start',
    ];

    public function __construct(private ?Node $node = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            return;
        }

        if (! is_array($value)) {
            $fail('The hardware profile must be an object of Proxmox settings.');

            return;
        }

        $schema = app(AnchorSchemaService::class)->forNode($this->node);

        foreach ($value as $key => $setting) {
            if (in_array($key, OsProfiles::META_KEYS, true)) {
                $this->checkSlot($key, $setting, $fail);

                continue;
            }

            if (in_array($key, self::COMPUTED_KEYS, true) || preg_match(self::COMPUTED_PATTERN, (string) $key)) {
                $fail("The panel sets `{$key}` itself when the server is built; it cannot be part of the profile.");

                continue;
            }

            if (! array_key_exists($key, $schema)) {
                $fail("Proxmox does not accept a `{$key}` setting when creating a guest.");

                continue;
            }

            $this->checkAgainstSchema($key, $setting, $schema[$key], $fail);
        }
    }

    /**
     * The meta keys name a slot rather than carrying a Proxmox value, because
     * the storage half of the argument is not known until build time.
     */
    private function checkSlot(string $key, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(?:scsi|ide|sata|virtio)\d+$/', $value)) {
            $fail("`{$key}` must name a disk slot, such as `scsi0` or `ide2`.");
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function checkAgainstSchema(string $key, mixed $value, array $definition, Closure $fail): void
    {
        $enum = $definition['enum'] ?? null;

        if (is_array($enum) && ! in_array((string) $value, array_map('strval', $enum), true)) {
            $fail("`{$key}` must be one of: ".implode(', ', $enum).'.');

            return;
        }

        // Proxmox's booleans arrive as 0/1 as often as true/false, and its
        // integers as numeric strings, so this checks what the value *means*
        // rather than what PHP happens to have decoded it as.
        match ($definition['type'] ?? 'string') {
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true)
                || $fail("`{$key}` must be true or false."),
            'integer', 'number' => is_numeric($value)
                || $fail("`{$key}` must be a number."),
            default => is_scalar($value)
                || $fail("`{$key}` must be a single value, not a list."),
        };
    }
}
