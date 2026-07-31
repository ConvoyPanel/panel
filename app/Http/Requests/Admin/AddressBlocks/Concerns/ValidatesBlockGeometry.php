<?php

namespace App\Http\Requests\Admin\AddressBlocks\Concerns;

use App\Enums\Network\AddressVersion;
use App\Models\AddressBlock;
use Illuminate\Contracts\Validation\Validator;

/**
 * Cross-field checks on a block's geometry. The per-field rules can only bound each prefix to
 * 0..128 in isolation, which is not enough to tell a usable block from one that generates nothing
 * — or one that reaches a negative bit shift in GenerateAddressesAction and 500s.
 */
trait ValidatesBlockGeometry
{
    protected function validateBlockGeometry(Validator $validator, AddressVersion $version): void
    {
        $validator->after(function (Validator $validator) use ($version) {
            // The per-field rules run first; without numeric prefixes there is nothing to compare.
            if ($validator->errors()->hasAny(['prefix_length_from', 'prefix_length_to'])) {
                return;
            }

            $from = $this->integer('prefix_length_from');
            $to = $this->integer('prefix_length_to');
            $max = $version === AddressVersion::IPv4 ? 32 : 128;

            foreach (['prefix_length_from' => $from, 'prefix_length_to' => $to] as $attribute => $value) {
                if ($value > $max) {
                    $validator->errors()->add(
                        $attribute,
                        "The {$attribute} may not be greater than {$max} for an {$version->value} block.",
                    );
                }
            }

            if ($validator->errors()->hasAny(['prefix_length_from', 'prefix_length_to'])) {
                return;
            }

            if ($to < $from) {
                $validator->errors()->add(
                    'prefix_length_to',
                    'The output prefix length must be at least the source prefix length — a block cannot hand out units larger than itself.',
                );

                return;
            }

            $this->validateGatewayLeavesCapacity($validator, $from, $to);
        });
    }

    /**
     * A block whose gateway sits inside its only allocatable unit has no capacity at all: that unit
     * is auto-reserved, so generation produces one locked row and nothing else. Surface it here
     * rather than letting an operator discover it after hitting Generate.
     */
    private function validateGatewayLeavesCapacity(Validator $validator, int $from, int $to): void
    {
        /** @var ?string $gateway */
        $gateway = $this->input('gateway');

        // Both addresses have to be parseable before the containment check means anything.
        if (empty($gateway) || $from !== $to || $validator->errors()->hasAny(['base_ip', 'gateway'])) {
            return;
        }

        // No version to set — the block reads it back off base_ip.
        $block = new AddressBlock([
            'base_ip' => $this->string('base_ip')->toString(),
            'gateway' => $gateway,
            'prefix_length_from' => $from,
            'prefix_length_to' => $to,
        ]);

        if ($block->containsAddress($gateway)) {
            $validator->errors()->add(
                'gateway',
                'The gateway falls inside the block\'s only allocatable unit, which leaves nothing to allocate. Widen the output prefix length or move the gateway outside this block.',
            );
        }
    }
}
