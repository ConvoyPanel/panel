<?php

namespace App\Enums\Server;

enum DiskInterface: string
{
    case IDE0 = 'ide0';
    case IDE1 = 'ide1';
    case IDE2 = 'ide2';
    case IDE3 = 'ide3';
    case SCSI0 = 'scsi0';
    case SCSI1 = 'scsi1';
    case SCSI2 = 'scsi2';
    case SCSI3 = 'scsi3';
    case SCSI4 = 'scsi4';
    case SCSI5 = 'scsi5';
    case SCSI6 = 'scsi6';
    case SCSI7 = 'scsi7';
    case SCSI8 = 'scsi8';
    case SCSI9 = 'scsi9';
    case SCSI10 = 'scsi10';
    case SCSI11 = 'scsi11';
    case SCSI12 = 'scsi12';
    case SCSI13 = 'scsi13';
    case SCSI14 = 'scsi14';
    case SCSI15 = 'scsi15';
    case SCSI16 = 'scsi16';
    case SCSI17 = 'scsi17';
    case SCSI18 = 'scsi18';
    case SCSI19 = 'scsi19';
    case SCSI20 = 'scsi20';
    case SCSI21 = 'scsi21';
    case SCSI22 = 'scsi22';
    case SCSI23 = 'scsi23';
    case SCSI24 = 'scsi24';
    case SCSI25 = 'scsi25';
    case SCSI26 = 'scsi26';
    case SCSI27 = 'scsi27';
    case SCSI28 = 'scsi28';
    case SCSI29 = 'scsi29';
    case SCSI30 = 'scsi30';
    case VIRTIO0 = 'virtio0';
    case VIRTIO1 = 'virtio1';
    case VIRTIO2 = 'virtio2';
    case VIRTIO3 = 'virtio3';
    case VIRTIO4 = 'virtio4';
    case VIRTIO5 = 'virtio5';
    case VIRTIO6 = 'virtio6';
    case VIRTIO7 = 'virtio7';
    case VIRTIO8 = 'virtio8';
    case VIRTIO9 = 'virtio9';
    case VIRTIO10 = 'virtio10';
    case VIRTIO11 = 'virtio11';
    case VIRTIO12 = 'virtio12';
    case VIRTIO13 = 'virtio13';
    case VIRTIO14 = 'virtio14';
    case VIRTIO15 = 'virtio15';
    case SATA0 = 'sata0';
    case SATA1 = 'sata1';
    case SATA2 = 'sata2';
    case SATA3 = 'sata3';
    case SATA4 = 'sata4';
    case SATA5 = 'sata5';
    case EFIDISK0 = 'efidisk0';
    case TPMSTATE0 = 'tpmstate0';
    
    /**
     * Get the maximum number of devices allowed for each interface type
     */
    public static function getMaxDevices(string $interfaceType): int
    {
        return match (strtolower($interfaceType)) {
            'ide' => 4,       // IDE0-IDE3
            'sata' => 6,      // SATA0-SATA5
            'scsi' => 31,     // SCSI0-SCSI30
            'virtio' => 16,   // VIRTIO0-VIRTIO15
            'efidisk' => 1,   // EFIDISK0 only
            'tpmstate' => 1,  // TPMSTATE0 only
            default => throw new \InvalidArgumentException("Unknown interface type: {$interfaceType}"),
        };
    }
    
    /**
     * Get the base interface type (ide, sata, scsi, virtio, efidisk, tpmstate)
     */
    public function getBaseType(): string
    {
        if (preg_match('/^([a-z]+)\d+$/', $this->value, $matches)) {
            return $matches[1];
        }
        
        throw new \RuntimeException("Could not determine base type for {$this->value}");
    }
    
    /**
     * Get the slot number for this interface
     */
    public function getSlot(): int
    {
        if (preg_match('/^[a-z]+(\d+)$/', $this->value, $matches)) {
            return (int) $matches[1];
        }
        
        throw new \RuntimeException("Could not determine slot number for {$this->value}");
    }
    
    /**
     * Check if a given interface type has available slots
     * 
     * @param string $interfaceType Base interface type (ide, sata, scsi, virtio, etc.)
     * @param array $usedSlots Array of slot numbers already in use
     * @return bool True if there are available slots
     */
    public static function hasAvailableSlots(string $interfaceType, array $usedSlots): bool
    {
        $maxSlots = self::getMaxDevices($interfaceType);
        
        // If we have fewer used slots than max, there's availability
        return count($usedSlots) < $maxSlots;
    }
    
    /**
     * Get the next available slot for a given interface type
     * 
     * @param string $interfaceType Base interface type (ide, sata, scsi, virtio, etc.)
     * @param array $usedSlots Array of slot numbers already in use
     * @return int|null The next available slot or null if none available
     */
    public static function getNextAvailableSlot(string $interfaceType, array $usedSlots): ?int
    {
        $maxSlots = self::getMaxDevices($interfaceType);
        
        // Find the first unused slot
        for ($i = 0; $i < $maxSlots; $i++) {
            if (!in_array($i, $usedSlots)) {
                return $i;
            }
        }
        
        return null; // No available slots
    }
    
    /**
     * Check if a specified interface and slot is valid
     */
    public static function isValid(string $interfaceType, int $slot): bool
    {
        try {
            $maxSlots = self::getMaxDevices($interfaceType);
            return $slot >= 0 && $slot < $maxSlots;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
