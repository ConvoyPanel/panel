<?php

use App\Data\Server\Proxmox\Firewall\FirewallLogEntryData;
use App\Enums\Server\Firewall\RuleAction;
use App\Enums\Server\Firewall\RuleDirection;

it('pulls readable fields out of a dropped inbound packet', function () {
    $entry = FirewallLogEntryData::fromRaw([
        'n' => 42,
        't' => '103 6 tap103i0-IN 16/Aug/2026:09:14:22 -0400 policy DROP: IN=fwbr103i0 OUT=fwbr103i0 PHYSIN=fwln103i0 PHYSOUT=tap103i0 SRC=45.83.64.12 DST=10.0.10.24 LEN=60 TOS=0x00 TTL=51 ID=54321 DF PROTO=TCP SPT=51422 DPT=22 WINDOW=64240 SYN URGP=0',
    ]);

    expect($entry->lineNumber)->toBe(42)
        ->and($entry->action)->toBe(RuleAction::Drop)
        ->and($entry->direction)->toBe(RuleDirection::Inbound)
        ->and($entry->sourceAddress)->toBe('45.83.64.12')
        ->and($entry->destinationAddress)->toBe('10.0.10.24')
        ->and($entry->sourcePort)->toBe(51422)
        ->and($entry->destinationPort)->toBe(22)
        ->and($entry->protocol)->toBe('tcp')
        ->and($entry->loggedAt?->format('Y-m-d H:i:s'))->toBe('2026-08-16 09:14:22');
});

it('reads direction from the outbound chain', function () {
    $entry = FirewallLogEntryData::fromRaw([
        'n' => 1,
        't' => '103 6 tap103i0-OUT 16/Aug/2026:09:08:03 -0400 7 REJECT: SRC=10.0.10.24 DST=209.85.233.27 PROTO=TCP SPT=44120 DPT=25',
    ]);

    expect($entry->direction)->toBe(RuleDirection::Outbound)
        ->and($entry->action)->toBe(RuleAction::Reject)
        ->and($entry->destinationPort)->toBe(25);
});

it('keeps an unparseable line rather than dropping or throwing on it', function () {
    // The format is not a documented contract, so one odd line must never take
    // out the whole log view.
    $entry = FirewallLogEntryData::fromRaw([
        'n' => 7,
        't' => 'starting firewall logger',
    ]);

    expect($entry->raw)->toBe('starting firewall logger')
        ->and($entry->lineNumber)->toBe(7)
        ->and($entry->action)->toBeNull()
        ->and($entry->direction)->toBeNull()
        ->and($entry->sourceAddress)->toBeNull()
        ->and($entry->loggedAt)->toBeNull();
});

it('survives an empty line', function () {
    $entry = FirewallLogEntryData::fromRaw(['n' => 0, 't' => '']);

    expect($entry->raw)->toBe('')
        ->and($entry->action)->toBeNull();
});

it('always preserves the raw line for the expanded view', function () {
    $raw = '103 6 tap103i0-IN 16/Aug/2026:09:14:22 -0400 policy DROP: SRC=1.2.3.4 DST=5.6.7.8 PROTO=UDP SPT=1 DPT=53';

    expect(FirewallLogEntryData::fromRaw(['n' => 1, 't' => $raw])->raw)->toBe($raw);
});
