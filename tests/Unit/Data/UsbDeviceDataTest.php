<?php

use App\Data\Server\Proxmox\Config\UsbDeviceData;

it('parses a bare host as the default key', function () {
    $usb = UsbDeviceData::fromRaw('usb0', '1-5');

    expect($usb->id)->toBe(0)
        ->and($usb->host)->toBe('1-5')
        ->and($usb->mapping)->toBeNull()
        ->and($usb->isUsb3)->toBeFalse();
});

it('parses a host= keyed head with the usb3 flag', function () {
    $usb = UsbDeviceData::fromRaw('usb2', 'host=0665:5161,usb3=1');

    expect($usb->id)->toBe(2)
        ->and($usb->host)->toBe('0665:5161')
        ->and($usb->isUsb3)->toBeTrue();
});

it('parses a cluster-mapped device that leads with mapping=', function () {
    $usb = UsbDeviceData::fromRaw('usb1', 'mapping=my-scanner,usb3=1');

    expect($usb->host)->toBeNull()
        ->and($usb->mapping)->toBe('my-scanner')
        ->and($usb->isUsb3)->toBeTrue();
});

it('parses mapping from the key=value tail', function () {
    $usb = UsbDeviceData::fromRaw('usb0', 'host=spice,mapping=my-scanner');

    expect($usb->host)->toBe('spice')
        ->and($usb->mapping)->toBe('my-scanner');
});

it('treats an absent usb3 flag as false', function () {
    expect(UsbDeviceData::fromRaw('usb0', 'host=spice')->isUsb3)->toBeFalse();
});
