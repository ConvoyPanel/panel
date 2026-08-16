<?php

use App\Data\Server\Proxmox\Firewall\FirewallRuleData;
use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\RuleAction;
use App\Enums\Server\Firewall\RuleDirection;

it('maps a Proxmox rule onto domain properties', function () {
    $rule = FirewallRuleData::fromRaw([
        'pos' => 3,
        'type' => 'in',
        'action' => 'ACCEPT',
        'enable' => 1,
        'proto' => 'tcp',
        'dport' => '80,443',
        'sport' => '1024:65535',
        'source' => '+trusted',
        'dest' => '10.0.0.5',
        'iface' => 'net0',
        'log' => 'info',
        'comment' => 'Public web',
        // Response-only, and never sent back.
        'ipversion' => 4,
    ]);

    expect($rule->position)->toBe(3)
        ->and($rule->direction)->toBe(RuleDirection::Inbound)
        ->and($rule->action)->toBe(RuleAction::Accept)
        ->and($rule->isEnabled)->toBeTrue()
        ->and($rule->protocol)->toBe('tcp')
        ->and($rule->destinationPort)->toBe('80,443')
        ->and($rule->sourcePort)->toBe('1024:65535')
        ->and($rule->sourceAddress)->toBe('+trusted')
        ->and($rule->destinationAddress)->toBe('10.0.0.5')
        ->and($rule->interface)->toBe('net0')
        ->and($rule->logLevel)->toBe(FirewallLogLevel::Info)
        ->and($rule->comment)->toBe('Public web');
});

it('round-trips a rule back to the same Proxmox keys', function () {
    $raw = [
        'pos' => 0,
        'type' => 'out',
        'action' => 'REJECT',
        'enable' => 1,
        'proto' => 'tcp',
        'dport' => '25',
        'comment' => 'No outbound mail',
    ];

    $payload = FirewallRuleData::fromRaw($raw)->toPayload();

    expect($payload)->toBe([
        'type' => 'out',
        'action' => 'REJECT',
        'enable' => 1,
        'proto' => 'tcp',
        'dport' => '25',
        'comment' => 'No outbound mail',
    ]);
});

it('sends enable as an integer, never a boolean', function () {
    // Rule-level `enable` is an integer in the PVE schema, unlike the boolean
    // of the same name in firewall options. Sending true/false here is the
    // classic silent failure.
    $enabled = FirewallRuleData::fromRaw(['pos' => 0, 'type' => 'in', 'action' => 'ACCEPT', 'enable' => 1]);
    $disabled = FirewallRuleData::fromRaw(['pos' => 0, 'type' => 'in', 'action' => 'ACCEPT', 'enable' => 0]);

    expect($enabled->toPayload()['enable'])->toBe(1)
        ->and($disabled->toPayload()['enable'])->toBe(0)
        ->and($disabled->isEnabled)->toBeFalse();
});

it('treats an absent enable as enabled, matching Proxmox', function () {
    $rule = FirewallRuleData::fromRaw(['pos' => 0, 'type' => 'in', 'action' => 'ACCEPT']);

    expect($rule->isEnabled)->toBeTrue();
});

it('omits unset fields rather than sending them empty', function () {
    $payload = FirewallRuleData::fromRaw([
        'pos' => 0,
        'type' => 'in',
        'action' => 'DROP',
        'enable' => 1,
    ])->toPayload();

    expect($payload)->not->toHaveKey('comment')
        ->and($payload)->not->toHaveKey('source')
        ->and($payload)->not->toHaveKey('proto');
});

it('names dropped fields so an update can clear them', function () {
    $previous = FirewallRuleData::fromRaw([
        'pos' => 0,
        'type' => 'in',
        'action' => 'ACCEPT',
        'enable' => 1,
        'proto' => 'tcp',
        'dport' => '22',
        'source' => '10.0.0.0/8',
        'comment' => 'SSH',
    ]);

    // The same rule with the source and comment removed.
    $next = new FirewallRuleData(
        position: 0,
        direction: RuleDirection::Inbound,
        action: RuleAction::Accept,
        isEnabled: true,
        macro: null,
        protocol: 'tcp',
        sourceAddress: null,
        destinationAddress: null,
        sourcePort: null,
        destinationPort: '22',
        icmpType: null,
        interface: null,
        logLevel: null,
        comment: '',
        digest: null,
    );

    // An empty string counts as cleared: Proxmox does not treat it as unset,
    // so it has to be named in `delete` like an outright null.
    expect($next->clearedKeysAgainst($previous))
        ->toEqualCanonicalizing(['source', 'comment']);
});

it('reports nothing to clear when a rule only gains fields', function () {
    $previous = FirewallRuleData::fromRaw([
        'pos' => 0, 'type' => 'in', 'action' => 'ACCEPT', 'enable' => 1,
    ]);

    $next = FirewallRuleData::fromRaw([
        'pos' => 0, 'type' => 'in', 'action' => 'ACCEPT', 'enable' => 1,
        'comment' => 'Now explained',
    ]);

    expect($next->clearedKeysAgainst($previous))->toBe([]);
});
