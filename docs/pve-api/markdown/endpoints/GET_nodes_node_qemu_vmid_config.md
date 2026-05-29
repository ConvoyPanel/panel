# GET /nodes/{node}/qemu/{vmid}/config

Get the virtual machine configuration with pending configuration changes applied. Set the 'current' parameter to get the current configuration instead.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| current | boolean | no | Get current values (instead of pending values). |
| snapshot | string | no | Fetch config values from given snapshot. |

## Returns

```json
{
  "description": "The VM configuration.",
  "properties": {
    "acpi": {
      "default": 1,
      "description": "Enable/disable ACPI.",
      "optional": 1,
      "type": "boolean"
    },
    "affinity": {
      "description": "List of host cores used to execute guest processes, for example: 0,5,8-11",
      "format": "pve-cpuset",
      "optional": 1,
      "type": "string"
    },
    "agent": {
      "description": "Enable/disable communication with the QEMU Guest Agent and its properties.",
      "format": {
        "enabled": {
          "default": 0,
          "default_key": 1,
          "description": "Enable/disable communication with a QEMU Guest Agent (QGA) running in the VM.",
          "type": "boolean"
        },
        "freeze-fs": {
          "default": 1,
          "description": "Freeze guest filesystems through QGA for consistent disk state on operations such as snapshots, backups, replications and clones.",
          "optional": 1,
          "type": "boolean",
          "verbose_description": "Whether to issue the guest-fsfreeze-freeze and guest-fsfreeze-thaw QEMU guest agent commands. Backups in snapshot mode, clones, snapshots without RAM, importing disks from a running guest, and replications normally issue a guest-fsfreeze-freeze and a respective thaw command when the QEMU Guest agent option is enabled in the guest's configuration and the agent is running inside of the guest.\n\nThe deprecated 'freeze-fs-on-backup' setting is treated as an alias for this setting."
        },
        "freeze-fs-on-backup": {
          "alias": "freeze-fs"
        },
        "fstrim_cloned_disks": {
          "default": 0,
          "description": "Run fstrim after moving a disk or migrating the VM.",
          "optional": 1,
          "type": "boolean"
        },
        "guest-fsfreeze": {
          "alias": "freeze-fs"
        },
        "type": {
          "default": "virtio",
          "description": "Select the agent type",
          "enum": [
            "virtio",
            "isa"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "allow-ksm": {
      "default": 1,
      "description": "Allow memory pages of this guest to be merged via KSM (Kernel Samepage Merging).",
      "optional": 1,
      "type": "boolean"
    },
    "amd-sev": {
      "description": "Secure Encrypted Virtualization (SEV) features by AMD CPUs",
      "format": "pve-qemu-sev-fmt",
      "optional": 1,
      "type": "string"
    },
    "arch": {
      "description": "Virtual processor architecture. Defaults to the host architecture.",
      "enum": [
        "x86_64",
        "aarch64"
      ],
      "optional": 1,
      "type": "string"
    },
    "args": {
      "description": "Arbitrary arguments passed to kvm.",
      "optional": 1,
      "type": "string",
      "verbose_description": "Arbitrary arguments passed to kvm, for example:\n\nargs: -no-reboot -smbios 'type=0,vendor=FOO'\n\nNOTE: this option is for experts only.\n"
    },
    "audio0": {
      "description": "Configure a audio device, useful in combination with QXL/Spice.",
      "format": {
        "device": {
          "description": "Configure an audio device.",
          "enum": [
            "ich9-intel-hda",
            "intel-hda",
            "AC97"
          ],
          "type": "string"
        },
        "driver": {
          "default": "spice",
          "description": "Driver backend for the audio device.",
          "enum": [
            "spice",
            "none"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "autostart": {
      "default": 0,
      "description": "Automatic restart after crash (currently ignored).",
      "optional": 1,
      "type": "boolean"
    },
    "balloon": {
      "description": "Amount of target RAM for the VM in MiB. The balloon driver is enabled by default, unless it is explicitly disabled by setting the value to zero.",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "bios": {
      "default": "seabios",
      "description": "Select BIOS implementation.",
      "enum": [
        "seabios",
        "ovmf"
      ],
      "optional": 1,
      "type": "string"
    },
    "boot": {
      "description": "Specify guest boot order. Use the 'order=' sub-property as usage with no key or 'legacy=' is deprecated.",
      "format": "pve-qm-boot",
      "optional": 1,
      "type": "string"
    },
    "bootdisk": {
      "description": "Enable booting from specified disk. Deprecated: Use 'boot: order=foo;bar' instead.",
      "format": "pve-qm-bootdisk",
      "optional": 1,
      "pattern": "(ide|sata|scsi|virtio)\\d+",
      "type": "string"
    },
    "cdrom": {
      "description": "This is an alias for option -ide2",
      "format": "pve-qm-ide",
      "optional": 1,
      "type": "string",
      "typetext": "<volume>"
    },
    "cicustom": {
      "description": "cloud-init: Specify custom files to replace the automatically generated ones at start.",
      "format": "pve-qm-cicustom",
      "optional": 1,
      "type": "string"
    },
    "cipassword": {
      "description": "cloud-init: Password to assign the user. Using this is generally not recommended. Use ssh keys instead. Also note that older cloud-init versions do not support hashed passwords.",
      "optional": 1,
      "type": "string"
    },
    "citype": {
      "description": "Specifies the cloud-init configuration format. The default depends on the configured operating system type (`ostype`. We use the `nocloud` format for Linux, and `configdrive2` for windows.",
      "enum": [
        "configdrive2",
        "nocloud",
        "opennebula"
      ],
      "optional": 1,
      "type": "string"
    },
    "ciupgrade": {
      "default": 1,
      "description": "cloud-init: do an automatic package upgrade after the first boot.",
      "optional": 1,
      "type": "boolean"
    },
    "ciuser": {
      "description": "cloud-init: User name to change ssh keys and password for instead of the image's configured default user.",
      "optional": 1,
      "type": "string"
    },
    "cores": {
      "default": 1,
      "description": "The number of cores per socket.",
      "minimum": 1,
      "optional": 1,
      "type": "integer"
    },
    "cpu": {
      "description": "Emulated CPU type.",
      "format": "pve-vm-cpu-conf",
      "optional": 1,
      "type": "string"
    },
    "cpulimit": {
      "default": 0,
      "description": "Limit of CPU usage.",
      "maximum": 128,
      "minimum": 0,
      "optional": 1,
      "type": "number",
      "verbose_description": "Limit of CPU usage.\n\nNOTE: If the computer has 2 CPUs, it has total of '2' CPU time. Value '0' indicates no CPU limit."
    },
    "cpuunits": {
      "default": "cgroup v1: 1024, cgroup v2: 100",
      "description": "CPU weight for a VM, will be clamped to [1, 10000] in cgroup v2.",
      "maximum": 262144,
      "minimum": 1,
      "optional": 1,
      "type": "integer",
      "verbose_description": "CPU weight for a VM. Argument is used in the kernel fair scheduler. The larger the number is, the more CPU time this VM gets. Number is relative to weights of all the other running VMs."
    },
    "description": {
      "description": "Description for the VM. Shown in the web-interface VM's summary. This is saved as comment inside the configuration file.",
      "maxLength": 8192,
      "optional": 1,
      "type": "string"
    },
    "digest": {
      "description": "SHA1 digest of configuration file. This can be used to prevent concurrent modifications.",
      "type": "string"
    },
    "efidisk0": {
      "description": "Configure a disk for storing EFI vars.",
      "format": {
        "efitype": {
          "default": "2m",
          "description": "Size and type of the OVMF EFI vars. '4m' is newer and recommended, and required for Secure Boot. For backwards compatibility, '2m' is used if not otherwise specified. Ignored for VMs with arch=aarch64 (ARM).",
          "enum": [
            "2m",
            "4m"
          ],
          "optional": 1,
          "type": "string"
        },
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id-or-qm-path",
          "format_description": "volume",
          "type": "string"
        },
        "format": {
          "description": "The drive's backing file's data format.",
          "enum": [
            "raw",
            "qcow",
            "qed",
            "qcow2",
            "vmdk",
            "cloop"
          ],
          "optional": 1,
          "type": "string"
        },
        "ms-cert": {
          "default": "2011",
          "description": "Informational marker indicating the version of the latest Microsoft UEFI certificates that have been enrolled by Proxmox VE. The value '2023k' means that the 'Microsoft UEFI CA 2023', the 'Windows UEFI CA 2023' and the 'Microsoft Corporation KEK 2K CA 2023' certificates are included. The values '2023' and '2023w' are deprecated and for compatibility only.",
          "enum": [
            "2011",
            "2023",
            "2023w",
            "2023k"
          ],
          "optional": 1,
          "type": "string"
        },
        "pre-enrolled-keys": {
          "default": 0,
          "description": "Use am EFI vars template with distribution-specific and Microsoft Standard keys enrolled, if used with 'efitype=4m'. Note that this will enable Secure Boot by default, though it can still be turned off from within the VM.",
          "optional": 1,
          "type": "boolean"
        },
        "size": {
          "description": "Disk size. This is purely informational and has no effect.",
          "format": "disk-size",
          "format_description": "DiskSize",
          "optional": 1,
          "type": "string"
        },
        "volume": {
          "alias": "file"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "freeze": {
      "description": "Freeze CPU at startup (use 'c' monitor command to start execution).",
      "optional": 1,
      "type": "boolean"
    },
    "hookscript": {
      "description": "Script that will be executed during various steps in the vms lifetime.",
      "format": "pve-volume-id",
      "optional": 1,
      "type": "string"
    },
    "hostpci[n]": {
      "description": "Map host PCI devices into guest.",
      "format": "pve-qm-hostpci",
      "optional": 1,
      "type": "string",
      "verbose_description": "Map host PCI devices into guest.\n\nNOTE: This option allows direct access to host hardware. So it is no longer\npossible to migrate such machines - use with special care.\n\nCAUTION: Experimental! User reported problems with this option.\n"
    },
    "hotplug": {
      "default": "network,disk,usb",
      "description": "Selectively enable hotplug features. This is a comma separated list of hotplug features: 'network', 'disk', 'cpu', 'memory', 'usb' and 'cloudinit'. Use '0' to disable hotplug completely. Using '1' as value is an alias for the default `network,disk,usb`. USB hotplugging is possible for guests with machine version >= 7.1 and ostype l26 or windows > 7.",
      "format": "pve-hotplug-features",
      "optional": 1,
      "type": "string"
    },
    "hugepages": {
      "description": "Enables hugepages memory.\n\nSets the size of hugepages in MiB. If the value is set to 'any' then 1 GiB hugepages will be used if possible, otherwise the size will fall back to 2 MiB.",
      "enum": [
        "any",
        "2",
        "1024"
      ],
      "optional": 1,
      "type": "string"
    },
    "ide[n]": {
      "description": "Use volume as IDE hard disk or CD-ROM (n is 0 to 3).",
      "format": {
        "aio": {
          "description": "AIO type to use.",
          "enum": [
            "native",
            "threads",
            "io_uring"
          ],
          "optional": 1,
          "type": "string"
        },
        "backup": {
          "description": "Whether the drive should be included when making backups.",
          "optional": 1,
          "type": "boolean"
        },
        "bps": {
          "description": "Maximum r/w speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_rd": {
          "description": "Maximum read speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_rd_length": {
          "alias": "bps_rd_max_length"
        },
        "bps_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_wr": {
          "description": "Maximum write speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_wr_length": {
          "alias": "bps_wr_max_length"
        },
        "bps_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "cache": {
          "description": "The drive's cache mode",
          "enum": [
            "none",
            "writethrough",
            "writeback",
            "unsafe",
            "directsync"
          ],
          "optional": 1,
          "type": "string"
        },
        "detect_zeroes": {
          "description": "Controls whether to detect and try to optimize writes of zeroes.",
          "optional": 1,
          "type": "boolean"
        },
        "discard": {
          "description": "Controls whether to pass discard/trim requests to the underlying storage.",
          "enum": [
            "ignore",
            "on"
          ],
          "optional": 1,
          "type": "string"
        },
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id-or-qm-path",
          "format_description": "volume",
          "type": "string"
        },
        "format": {
          "description": "The drive's backing file's data format.",
          "enum": [
            "raw",
            "qcow",
            "qed",
            "qcow2",
            "vmdk",
            "cloop"
          ],
          "optional": 1,
          "type": "string"
        },
        "iops": {
          "description": "Maximum r/w I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max": {
          "description": "Maximum unthrottled r/w I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_rd": {
          "description": "Maximum read I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_length": {
          "alias": "iops_rd_max_length"
        },
        "iops_rd_max": {
          "description": "Maximum unthrottled read I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_wr": {
          "description": "Maximum write I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_length": {
          "alias": "iops_wr_max_length"
        },
        "iops_wr_max": {
          "description": "Maximum unthrottled write I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "mbps": {
          "description": "Maximum r/w speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_max": {
          "description": "Maximum unthrottled r/w pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd": {
          "description": "Maximum read speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd_max": {
          "description": "Maximum unthrottled read pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr": {
          "description": "Maximum write speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr_max": {
          "description": "Maximum unthrottled write pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "media": {
          "default": "disk",
          "description": "The drive's media type.",
          "enum": [
            "cdrom",
            "disk"
          ],
          "optional": 1,
          "type": "string"
        },
        "model": {
          "description": "The drive's reported model name, url-encoded, up to 40 bytes long.",
          "format": "urlencoded",
          "format_description": "model",
          "maxLength": 120,
          "optional": 1,
          "type": "string"
        },
        "replicate": {
          "default": 1,
          "description": "Whether the drive should considered for replication jobs.",
          "optional": 1,
          "type": "boolean"
        },
        "rerror": {
          "description": "Read error action.",
          "enum": [
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "serial": {
          "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
          "format": "urlencoded",
          "format_description": "serial",
          "maxLength": 60,
          "optional": 1,
          "type": "string"
        },
        "shared": {
          "default": 0,
          "description": "Mark this locally-managed volume as available on all nodes",
          "optional": 1,
          "type": "boolean",
          "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
        },
        "size": {
          "description": "Disk size. This is purely informational and has no effect.",
          "format": "disk-size",
          "format_description": "DiskSize",
          "optional": 1,
          "type": "string"
        },
        "snapshot": {
          "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
          "optional": 1,
          "type": "boolean"
        },
        "ssd": {
          "description": "Whether to expose this drive as an SSD, rather than a rotational hard disk.",
          "optional": 1,
          "type": "boolean"
        },
        "volume": {
          "alias": "file"
        },
        "werror": {
          "description": "Write error action.",
          "enum": [
            "enospc",
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "wwn": {
          "description": "The drive's worldwide name, encoded as 16 bytes hex string, prefixed by '0x'.",
          "format_description": "wwn",
          "optional": 1,
          "pattern": "(?^:^(0x)[0-9a-fA-F]{16})",
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "intel-tdx": {
      "description": "Trusted Domain Extension (TDX) features by Intel CPUs",
      "format": "pve-qemu-tdx-fmt",
      "optional": 1,
      "type": "string"
    },
    "ipconfig[n]": {
      "description": "cloud-init: Specify IP addresses and gateways for the corresponding interface.\n\nIP addresses use CIDR notation, gateways are optional but need an IP of the same type specified.\n\nThe special string 'dhcp' can be used for IP addresses to use DHCP, in which case no explicit\ngateway should be provided.\nFor IPv6 the special string 'auto' can be used to use stateless autoconfiguration. This requires\ncloud-init 19.4 or newer.\n\nIf cloud-init is enabled and neither an IPv4 nor an IPv6 address is specified, it defaults to using\ndhcp on IPv4.\n",
      "format": "pve-qm-ipconfig",
      "optional": 1,
      "type": "string"
    },
    "ivshmem": {
      "description": "Inter-VM shared memory. Useful for direct communication between VMs, or to the host.",
      "format": {
        "name": {
          "description": "The name of the file. Will be prefixed with 'pve-shm-'. Default is the VMID. Will be deleted when the VM is stopped.",
          "format_description": "string",
          "optional": 1,
          "pattern": "[a-zA-Z0-9\\-]+",
          "type": "string"
        },
        "size": {
          "description": "The size of the file in MB.",
          "minimum": 1,
          "type": "integer"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "keephugepages": {
      "default": 0,
      "description": "Use together with hugepages. If enabled, hugepages will not not be deleted after VM shutdown and can be used for subsequent starts.",
      "optional": 1,
      "type": "boolean"
    },
    "keyboard": {
      "default": null,
      "description": "Keyboard layout for VNC server. This option is generally not required and is often better handled from within the guest OS.",
      "enum": [
        "de",
        "de-ch",
        "da",
        "en-gb",
        "en-us",
        "es",
        "fi",
        "fr",
        "fr-be",
        "fr-ca",
        "fr-ch",
        "hu",
        "is",
        "it",
        "ja",
        "lt",
        "mk",
        "nl",
        "no",
        "pl",
        "pt",
        "pt-br",
        "sv",
        "sl",
        "tr"
      ],
      "optional": 1,
      "type": "string"
    },
    "kvm": {
      "default": 1,
      "description": "Enable/disable KVM hardware virtualization.",
      "optional": 1,
      "type": "boolean"
    },
    "localtime": {
      "description": "Set the real time clock (RTC) to local time. This is enabled by default if the `ostype` indicates a Microsoft Windows OS.",
      "optional": 1,
      "type": "boolean"
    },
    "lock": {
      "description": "Lock/unlock the VM.",
      "enum": [
        "backup",
        "clone",
        "create",
        "migrate",
        "rollback",
        "snapshot",
        "snapshot-delete",
        "suspending",
        "suspended"
      ],
      "optional": 1,
      "type": "string"
    },
    "machine": {
      "description": "Specify the QEMU machine.",
      "format": {
        "aw-bits": {
          "description": "Specifies the vIOMMU address space bit width.",
          "maximum": 64,
          "minimum": 32,
          "optional": 1,
          "type": "number",
          "verbose_description": "Specifies the vIOMMU address space bit width.\n\nIntel vIOMMU supports a bit width of either 39 or 48 bits and VirtIO vIOMMU supports any bit width between 32 and 64 bits."
        },
        "enable-s3": {
          "description": "Enables S3 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
          "optional": 1,
          "type": "boolean"
        },
        "enable-s4": {
          "description": "Enables S4 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
          "optional": 1,
          "type": "boolean"
        },
        "type": {
          "default_key": 1,
          "description": "Specifies the QEMU machine type.",
          "format_description": "machine type",
          "maxLength": 40,
          "optional": 1,
          "pattern": "(pc|pc(-i440fx)?-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|q35|pc-q35-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|virt(?:-\\d+(\\.\\d+)+)?(\\+pve\\d+)?)",
          "type": "string"
        },
        "viommu": {
          "description": "Enable and set guest vIOMMU variant (Intel vIOMMU needs q35 to be set as machine type).",
          "enum": [
            "intel",
            "virtio"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "memory": {
      "description": "Memory properties.",
      "format": {
        "current": {
          "default": 512,
          "default_key": 1,
          "description": "Current amount of online RAM for the VM in MiB. This is the maximum available memory when you use the balloon device.",
          "minimum": 16,
          "type": "integer"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "meta": {
      "description": "Some (read-only) meta-information about this guest.",
      "format": {
        "creation-qemu": {
          "description": "The QEMU (machine) version from the time this VM was created.",
          "optional": 1,
          "pattern": "\\d+(\\.\\d+)+",
          "type": "string"
        },
        "ctime": {
          "description": "The guest creation timestamp as UNIX epoch time",
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "migrate_downtime": {
      "default": 0.1,
      "description": "Set maximum tolerated downtime (in seconds) for migrations. Should the migration not be able to converge in the very end, because too much newly dirtied RAM needs to be transferred, the limit will be increased automatically step-by-step until migration can converge. Will be capped to 2000 seconds (maximum in QEMU).",
      "minimum": 0,
      "optional": 1,
      "type": "number"
    },
    "migrate_speed": {
      "default": 0,
      "description": "Set maximum speed (in MB/s) for migrations. Value 0 is no limit.",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "name": {
      "description": "Set a name for the VM. Only used on the configuration web interface.",
      "format": "dns-name",
      "optional": 1,
      "type": "string"
    },
    "nameserver": {
      "description": "cloud-init: Sets DNS server IP address for a container. Create will automatically use the setting from the host if neither searchdomain nor nameserver are set.",
      "format": "address-list",
      "optional": 1,
      "type": "string"
    },
    "net[n]": {
      "description": "Specify network devices.",
      "format": {
        "bridge": {
          "description": "Bridge to attach the network device to. The Proxmox VE standard bridge\nis called 'vmbr0'.\n\nIf you do not specify a bridge, we create a kvm user (NATed) network\ndevice, which provides DHCP and DNS services. The following addresses\nare used:\n\n 10.0.2.2   Gateway\n 10.0.2.3   DNS Server\n 10.0.2.4   SMB Server\n\nThe DHCP server assign addresses to the guest starting from 10.0.2.15.\n",
          "format": "pve-bridge-id",
          "format_description": "bridge",
          "optional": 1,
          "type": "string"
        },
        "e1000": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "e1000-82540em": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "e1000-82544gc": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "e1000-82545em": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "e1000e": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "firewall": {
          "description": "Whether this interface should be protected by the firewall.",
          "optional": 1,
          "type": "boolean"
        },
        "i82551": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "i82557b": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "i82559er": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "link_down": {
          "description": "Whether this interface should be disconnected (like pulling the plug).",
          "optional": 1,
          "type": "boolean"
        },
        "macaddr": {
          "description": "MAC address. That address must be unique within your network. This is automatically generated if not specified.",
          "format": "mac-addr",
          "format_description": "XX:XX:XX:XX:XX:XX",
          "optional": 1,
          "type": "string",
          "verbose_description": "A common MAC address with the I/G (Individual/Group) bit not set."
        },
        "model": {
          "default_key": 1,
          "description": "Network Card Model. The 'virtio' model provides the best performance with very low CPU overhead. If your guest does not support this driver, it is usually best to use 'e1000'.",
          "enum": [
            "e1000",
            "e1000-82540em",
            "e1000-82544gc",
            "e1000-82545em",
            "e1000e",
            "i82551",
            "i82557b",
            "i82559er",
            "ne2k_isa",
            "ne2k_pci",
            "pcnet",
            "rtl8139",
            "virtio",
            "vmxnet3"
          ],
          "type": "string"
        },
        "mtu": {
          "description": "Force MTU of network device (VirtIO only). Setting to '1' or empty will use the bridge MTU",
          "maximum": 65520,
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "ne2k_isa": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "ne2k_pci": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "pcnet": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "queues": {
          "description": "Number of packet queues to be used on the device.",
          "maximum": 64,
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        },
        "rate": {
          "description": "Rate limit in mbps (megabytes per second) as floating point number.",
          "minimum": 0,
          "optional": 1,
          "type": "number"
        },
        "rtl8139": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "tag": {
          "description": "VLAN tag to apply to packets on this interface.",
          "maximum": 4094,
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "trunks": {
          "description": "VLAN trunks to pass through this interface.",
          "format_description": "vlanid[;vlanid...]",
          "optional": 1,
          "pattern": "(?^:\\d+(?:-\\d+)?(?:;\\d+(?:-\\d+)?)*)",
          "type": "string"
        },
        "virtio": {
          "alias": "macaddr",
          "keyAlias": "model"
        },
        "vmxnet3": {
          "alias": "macaddr",
          "keyAlias": "model"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "numa": {
      "default": 0,
      "description": "Enable/disable NUMA.",
      "optional": 1,
      "type": "boolean"
    },
    "numa[n]": {
      "description": "NUMA topology.",
      "format": {
        "cpus": {
          "description": "CPUs accessing this NUMA node.",
          "format_description": "id[-id];...",
          "pattern": "(?^:\\d+(?:-\\d+)?(?:;\\d+(?:-\\d+)?)*)",
          "type": "string"
        },
        "hostnodes": {
          "description": "Host NUMA nodes to use.",
          "format_description": "id[-id];...",
          "optional": 1,
          "pattern": "(?^:\\d+(?:-\\d+)?(?:;\\d+(?:-\\d+)?)*)",
          "type": "string"
        },
        "memory": {
          "description": "Amount of memory this NUMA node provides.",
          "optional": 1,
          "type": "number"
        },
        "policy": {
          "description": "NUMA allocation policy.",
          "enum": [
            "preferred",
            "bind",
            "interleave"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "onboot": {
      "default": 0,
      "description": "Specifies whether a VM will be started during system bootup.",
      "optional": 1,
      "type": "boolean"
    },
    "ostype": {
      "default": "other",
      "description": "Specify guest operating system.",
      "enum": [
        "other",
        "wxp",
        "w2k",
        "w2k3",
        "w2k8",
        "wvista",
        "win7",
        "win8",
        "win10",
        "win11",
        "l24",
        "l26",
        "solaris"
      ],
      "optional": 1,
      "type": "string",
      "verbose_description": "Specify guest operating system. This is used to enable special\noptimization/features for specific operating systems:\n\n[horizontal]\nother;; unspecified OS\nwxp;; Microsoft Windows XP\nw2k;; Microsoft Windows 2000\nw2k3;; Microsoft Windows 2003\nw2k8;; Microsoft Windows 2008\nwvista;; Microsoft Windows Vista\nwin7;; Microsoft Windows 7\nwin8;; Microsoft Windows 8/2012/2012r2\nwin10;; Microsoft Windows 10/2016/2019\nwin11;; Microsoft Windows 11/2022/2025\nl24;; Linux 2.4 Kernel\nl26;; Linux 2.6 - 7.X Kernel\nsolaris;; Solaris/OpenSolaris/OpenIndiania kernel\n"
    },
    "parallel[n]": {
      "description": "Map host parallel devices (n is 0 to 2).",
      "optional": 1,
      "pattern": "/dev/parport\\d+|/dev/usb/lp\\d+",
      "type": "string",
      "verbose_description": "Map host parallel devices (n is 0 to 2).\n\nNOTE: This option allows direct access to host hardware. So it is no longer possible to migrate such\nmachines - use with special care.\n\nCAUTION: Experimental! User reported problems with this option.\n"
    },
    "parent": {
      "description": "Parent snapshot name. This is used internally, and should not be modified.",
      "format": "pve-configid",
      "maxLength": 40,
      "optional": 1,
      "type": "string"
    },
    "protection": {
      "default": 0,
      "description": "Sets the protection flag of the VM. This will disable the remove VM and remove disk operations.",
      "optional": 1,
      "type": "boolean"
    },
    "reboot": {
      "default": 1,
      "description": "Allow reboot. If set to '0' the VM exit on reboot.",
      "optional": 1,
      "type": "boolean"
    },
    "rng0": {
      "description": "Configure a VirtIO-based Random Number Generator.",
      "format": "pve-qm-rng",
      "optional": 1,
      "type": "string"
    },
    "running-nets-host-mtu": {
      "description": "List of VirtIO network devices and their effective host_mtu setting. A value of 0 means that the host_mtu parameter is to be avoided for the corresponding device. This is used internally for snapshots.",
      "optional": 1,
      "pattern": "net\\d+=\\d+(,net\\d+=\\d+)*",
      "type": "string"
    },
    "runningcpu": {
      "description": "Specifies the QEMU '-cpu' parameter of the running vm. This is used internally for snapshots.",
      "format_description": "QEMU -cpu parameter",
      "optional": 1,
      "pattern": "(?^u:^((?>[+-]?[\\w\\-\\._=]+,?)+)$)",
      "type": "string"
    },
    "runningmachine": {
      "description": "Specifies the QEMU machine type of the running vm. This is used internally for snapshots.",
      "format": {
        "aw-bits": {
          "description": "Specifies the vIOMMU address space bit width.",
          "maximum": 64,
          "minimum": 32,
          "optional": 1,
          "type": "number",
          "verbose_description": "Specifies the vIOMMU address space bit width.\n\nIntel vIOMMU supports a bit width of either 39 or 48 bits and VirtIO vIOMMU supports any bit width between 32 and 64 bits."
        },
        "enable-s3": {
          "description": "Enables S3 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
          "optional": 1,
          "type": "boolean"
        },
        "enable-s4": {
          "description": "Enables S4 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
          "optional": 1,
          "type": "boolean"
        },
        "type": {
          "default_key": 1,
          "description": "Specifies the QEMU machine type.",
          "format_description": "machine type",
          "maxLength": 40,
          "optional": 1,
          "pattern": "(pc|pc(-i440fx)?-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|q35|pc-q35-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|virt(?:-\\d+(\\.\\d+)+)?(\\+pve\\d+)?)",
          "type": "string"
        },
        "viommu": {
          "description": "Enable and set guest vIOMMU variant (Intel vIOMMU needs q35 to be set as machine type).",
          "enum": [
            "intel",
            "virtio"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "sata[n]": {
      "description": "Use volume as SATA hard disk or CD-ROM (n is 0 to 5).",
      "format": {
        "aio": {
          "description": "AIO type to use.",
          "enum": [
            "native",
            "threads",
            "io_uring"
          ],
          "optional": 1,
          "type": "string"
        },
        "backup": {
          "description": "Whether the drive should be included when making backups.",
          "optional": 1,
          "type": "boolean"
        },
        "bps": {
          "description": "Maximum r/w speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_rd": {
          "description": "Maximum read speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_rd_length": {
          "alias": "bps_rd_max_length"
        },
        "bps_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_wr": {
          "description": "Maximum write speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_wr_length": {
          "alias": "bps_wr_max_length"
        },
        "bps_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "cache": {
          "description": "The drive's cache mode",
          "enum": [
            "none",
            "writethrough",
            "writeback",
            "unsafe",
            "directsync"
          ],
          "optional": 1,
          "type": "string"
        },
        "detect_zeroes": {
          "description": "Controls whether to detect and try to optimize writes of zeroes.",
          "optional": 1,
          "type": "boolean"
        },
        "discard": {
          "description": "Controls whether to pass discard/trim requests to the underlying storage.",
          "enum": [
            "ignore",
            "on"
          ],
          "optional": 1,
          "type": "string"
        },
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id-or-qm-path",
          "format_description": "volume",
          "type": "string"
        },
        "format": {
          "description": "The drive's backing file's data format.",
          "enum": [
            "raw",
            "qcow",
            "qed",
            "qcow2",
            "vmdk",
            "cloop"
          ],
          "optional": 1,
          "type": "string"
        },
        "iops": {
          "description": "Maximum r/w I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max": {
          "description": "Maximum unthrottled r/w I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_rd": {
          "description": "Maximum read I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_length": {
          "alias": "iops_rd_max_length"
        },
        "iops_rd_max": {
          "description": "Maximum unthrottled read I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_wr": {
          "description": "Maximum write I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_length": {
          "alias": "iops_wr_max_length"
        },
        "iops_wr_max": {
          "description": "Maximum unthrottled write I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "mbps": {
          "description": "Maximum r/w speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_max": {
          "description": "Maximum unthrottled r/w pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd": {
          "description": "Maximum read speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd_max": {
          "description": "Maximum unthrottled read pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr": {
          "description": "Maximum write speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr_max": {
          "description": "Maximum unthrottled write pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "media": {
          "default": "disk",
          "description": "The drive's media type.",
          "enum": [
            "cdrom",
            "disk"
          ],
          "optional": 1,
          "type": "string"
        },
        "replicate": {
          "default": 1,
          "description": "Whether the drive should considered for replication jobs.",
          "optional": 1,
          "type": "boolean"
        },
        "rerror": {
          "description": "Read error action.",
          "enum": [
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "serial": {
          "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
          "format": "urlencoded",
          "format_description": "serial",
          "maxLength": 60,
          "optional": 1,
          "type": "string"
        },
        "shared": {
          "default": 0,
          "description": "Mark this locally-managed volume as available on all nodes",
          "optional": 1,
          "type": "boolean",
          "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
        },
        "size": {
          "description": "Disk size. This is purely informational and has no effect.",
          "format": "disk-size",
          "format_description": "DiskSize",
          "optional": 1,
          "type": "string"
        },
        "snapshot": {
          "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
          "optional": 1,
          "type": "boolean"
        },
        "ssd": {
          "description": "Whether to expose this drive as an SSD, rather than a rotational hard disk.",
          "optional": 1,
          "type": "boolean"
        },
        "volume": {
          "alias": "file"
        },
        "werror": {
          "description": "Write error action.",
          "enum": [
            "enospc",
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "wwn": {
          "description": "The drive's worldwide name, encoded as 16 bytes hex string, prefixed by '0x'.",
          "format_description": "wwn",
          "optional": 1,
          "pattern": "(?^:^(0x)[0-9a-fA-F]{16})",
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "scsi[n]": {
      "description": "Use volume as SCSI hard disk or CD-ROM (n is 0 to 30).",
      "format": {
        "aio": {
          "description": "AIO type to use.",
          "enum": [
            "native",
            "threads",
            "io_uring"
          ],
          "optional": 1,
          "type": "string"
        },
        "backup": {
          "description": "Whether the drive should be included when making backups.",
          "optional": 1,
          "type": "boolean"
        },
        "bps": {
          "description": "Maximum r/w speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_rd": {
          "description": "Maximum read speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_rd_length": {
          "alias": "bps_rd_max_length"
        },
        "bps_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_wr": {
          "description": "Maximum write speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_wr_length": {
          "alias": "bps_wr_max_length"
        },
        "bps_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "cache": {
          "description": "The drive's cache mode",
          "enum": [
            "none",
            "writethrough",
            "writeback",
            "unsafe",
            "directsync"
          ],
          "optional": 1,
          "type": "string"
        },
        "detect_zeroes": {
          "description": "Controls whether to detect and try to optimize writes of zeroes.",
          "optional": 1,
          "type": "boolean"
        },
        "discard": {
          "description": "Controls whether to pass discard/trim requests to the underlying storage.",
          "enum": [
            "ignore",
            "on"
          ],
          "optional": 1,
          "type": "string"
        },
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id-or-qm-path",
          "format_description": "volume",
          "type": "string"
        },
        "format": {
          "description": "The drive's backing file's data format.",
          "enum": [
            "raw",
            "qcow",
            "qed",
            "qcow2",
            "vmdk",
            "cloop"
          ],
          "optional": 1,
          "type": "string"
        },
        "iops": {
          "description": "Maximum r/w I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max": {
          "description": "Maximum unthrottled r/w I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_rd": {
          "description": "Maximum read I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_length": {
          "alias": "iops_rd_max_length"
        },
        "iops_rd_max": {
          "description": "Maximum unthrottled read I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_wr": {
          "description": "Maximum write I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_length": {
          "alias": "iops_wr_max_length"
        },
        "iops_wr_max": {
          "description": "Maximum unthrottled write I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iothread": {
          "description": "Whether to use iothreads for this drive",
          "optional": 1,
          "type": "boolean"
        },
        "mbps": {
          "description": "Maximum r/w speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_max": {
          "description": "Maximum unthrottled r/w pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd": {
          "description": "Maximum read speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd_max": {
          "description": "Maximum unthrottled read pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr": {
          "description": "Maximum write speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr_max": {
          "description": "Maximum unthrottled write pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "media": {
          "default": "disk",
          "description": "The drive's media type.",
          "enum": [
            "cdrom",
            "disk"
          ],
          "optional": 1,
          "type": "string"
        },
        "product": {
          "description": "The drive's product name, up to 16 bytes long.",
          "format_description": "product",
          "optional": 1,
          "pattern": "[A-Za-z0-9\\-_\\s]{,16}",
          "type": "string"
        },
        "queues": {
          "description": "Number of queues.",
          "minimum": 2,
          "optional": 1,
          "type": "integer"
        },
        "replicate": {
          "default": 1,
          "description": "Whether the drive should considered for replication jobs.",
          "optional": 1,
          "type": "boolean"
        },
        "rerror": {
          "description": "Read error action.",
          "enum": [
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "ro": {
          "description": "Whether the drive is read-only.",
          "optional": 1,
          "type": "boolean"
        },
        "scsiblock": {
          "default": 0,
          "description": "whether to use scsi-block for full passthrough of host block device\n\nWARNING: can lead to I/O errors in combination with low memory or high memory fragmentation on host",
          "optional": 1,
          "type": "boolean"
        },
        "serial": {
          "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
          "format": "urlencoded",
          "format_description": "serial",
          "maxLength": 60,
          "optional": 1,
          "type": "string"
        },
        "shared": {
          "default": 0,
          "description": "Mark this locally-managed volume as available on all nodes",
          "optional": 1,
          "type": "boolean",
          "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
        },
        "size": {
          "description": "Disk size. This is purely informational and has no effect.",
          "format": "disk-size",
          "format_description": "DiskSize",
          "optional": 1,
          "type": "string"
        },
        "snapshot": {
          "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
          "optional": 1,
          "type": "boolean"
        },
        "ssd": {
          "description": "Whether to expose this drive as an SSD, rather than a rotational hard disk.",
          "optional": 1,
          "type": "boolean"
        },
        "vendor": {
          "description": "The drive's vendor name, up to 8 bytes long.",
          "format_description": "vendor",
          "optional": 1,
          "pattern": "[A-Za-z0-9\\-_\\s]{,8}",
          "type": "string"
        },
        "volume": {
          "alias": "file"
        },
        "werror": {
          "description": "Write error action.",
          "enum": [
            "enospc",
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "wwn": {
          "description": "The drive's worldwide name, encoded as 16 bytes hex string, prefixed by '0x'.",
          "format_description": "wwn",
          "optional": 1,
          "pattern": "(?^:^(0x)[0-9a-fA-F]{16})",
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "scsihw": {
      "default": "lsi",
      "description": "SCSI controller model",
      "enum": [
        "lsi",
        "lsi53c810",
        "virtio-scsi-pci",
        "virtio-scsi-single",
        "megasas",
        "pvscsi"
      ],
      "optional": 1,
      "type": "string"
    },
    "searchdomain": {
      "description": "cloud-init: Sets DNS search domains for a container. Create will automatically use the setting from the host if neither searchdomain nor nameserver are set.",
      "optional": 1,
      "type": "string"
    },
    "serial[n]": {
      "description": "Create a serial device inside the VM (n is 0 to 3)",
      "optional": 1,
      "pattern": "(/dev/[^,]+|socket)",
      "type": "string",
      "verbose_description": "Create a serial device inside the VM (n is 0 to 3), and pass through a\nhost serial device (i.e. /dev/ttyS0), or create a unix socket on the\nhost side (use 'qm terminal' to open a terminal connection).\n\nNOTE: If you pass through a host serial device, it is no longer possible to migrate such machines -\nuse with special care.\n\nCAUTION: Experimental! User reported problems with this option.\n"
    },
    "shares": {
      "default": 1000,
      "description": "Amount of memory shares for auto-ballooning. The larger the number is, the more memory this VM gets. Number is relative to weights of all other running VMs. Using zero disables auto-ballooning. Auto-ballooning is done by pvestatd.",
      "maximum": 50000,
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "smbios1": {
      "description": "Specify SMBIOS type 1 fields.",
      "format": "pve-qm-smbios1",
      "maxLength": 512,
      "optional": 1,
      "type": "string"
    },
    "smp": {
      "default": 1,
      "description": "The number of CPUs. Please use option -sockets instead.",
      "minimum": 1,
      "optional": 1,
      "type": "integer"
    },
    "snaptime": {
      "description": "Timestamp for snapshots.",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "sockets": {
      "default": 1,
      "description": "The number of CPU sockets.",
      "minimum": 1,
      "optional": 1,
      "type": "integer"
    },
    "spice_enhancements": {
      "description": "Configure additional enhancements for SPICE.",
      "format": {
        "foldersharing": {
          "default": "0",
          "description": "Enable folder sharing via SPICE. Needs Spice-WebDAV daemon installed in the VM.",
          "optional": 1,
          "type": "boolean"
        },
        "videostreaming": {
          "default": "off",
          "description": "Enable video streaming. Uses compression for detected video streams.",
          "enum": [
            "off",
            "all",
            "filter"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "sshkeys": {
      "description": "cloud-init: Setup public SSH keys (one key per line, OpenSSH format).",
      "format": "urlencoded",
      "optional": 1,
      "type": "string"
    },
    "startdate": {
      "default": "now",
      "description": "Set the initial date of the real time clock. Valid format for date are:'now' or '2006-06-17T16:01:21' or '2006-06-17'.",
      "optional": 1,
      "pattern": "(now|\\d{4}-\\d{1,2}-\\d{1,2}(T\\d{1,2}:\\d{1,2}:\\d{1,2})?)",
      "type": "string",
      "typetext": "(now | YYYY-MM-DD | YYYY-MM-DDTHH:MM:SS)"
    },
    "startup": {
      "description": "Startup and shutdown behavior. Order is a non-negative number defining the general startup order. Shutdown in done with reverse ordering. Additionally you can set the 'up' or 'down' delay in seconds, which specifies a delay to wait before the next VM is started or stopped.",
      "format": "pve-startup-order",
      "optional": 1,
      "type": "string",
      "typetext": "[[order=]\\d+] [,up=\\d+] [,down=\\d+] "
    },
    "tablet": {
      "default": 1,
      "description": "Enable/disable the USB tablet device.",
      "optional": 1,
      "type": "boolean",
      "verbose_description": "Enable/disable the USB tablet device. This device is usually needed to allow absolute mouse positioning with VNC. Else the mouse runs out of sync with normal VNC clients. If you're running lots of console-only guests on one host, you may consider disabling this to save some context switches. This is turned off by default if you use spice (`qm set <vmid> --vga qxl`)."
    },
    "tags": {
      "description": "Tags of the VM. This is only meta information.",
      "format": "pve-tag-list",
      "optional": 1,
      "type": "string"
    },
    "tdf": {
      "default": 0,
      "description": "Enable/disable time drift fix.",
      "optional": 1,
      "type": "boolean"
    },
    "template": {
      "default": 0,
      "description": "Enable/disable Template.",
      "optional": 1,
      "type": "boolean"
    },
    "tpmstate0": {
      "description": "Configure a Disk for storing TPM state. The format is fixed to 'raw'.",
      "format": {
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id-or-qm-path",
          "format_description": "volume",
          "type": "string"
        },
        "format": {
          "description": "Format of the image.",
          "enum": [
            "raw",
            "qcow2",
            "vmdk"
          ],
          "optional": 1,
          "type": "string"
        },
        "size": {
          "description": "Disk size. This is purely informational and has no effect.",
          "format": "disk-size",
          "format_description": "DiskSize",
          "optional": 1,
          "type": "string"
        },
        "version": {
          "default": "v1.2",
          "description": "The TPM interface version. v2.0 is newer and should be preferred. Note that this cannot be changed later on.",
          "enum": [
            "v1.2",
            "v2.0"
          ],
          "optional": 1,
          "type": "string"
        },
        "volume": {
          "alias": "file"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "unused[n]": {
      "description": "Reference to unused volumes. This is used internally, and should not be modified manually.",
      "format": {
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id",
          "format_description": "volume",
          "type": "string"
        },
        "volume": {
          "alias": "file"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "usb[n]": {
      "description": "Configure an USB device (n is 0 to 4, for machine version >= 7.1 and ostype l26 or windows > 7, n can be up to 14).",
      "format": {
        "host": {
          "default_key": 1,
          "description": "The Host USB device or port or the value 'spice'. HOSTUSBDEVICE syntax is:\n\n 'bus-port(.port)*' (decimal numbers) or\n 'vendor_id:product_id' (hexadecimal numbers) or\n 'spice'\n\nYou can use the 'lsusb -t' command to list existing usb devices.\n\nNOTE: This option allows direct access to host hardware. So it is no longer possible to migrate such\nmachines - use with special care.\n\nThe value 'spice' can be used to add a usb redirection devices for spice.\n\nEither this or the 'mapping' key must be set.\n",
          "format_description": "HOSTUSBDEVICE|spice",
          "optional": 1,
          "pattern": "(?^:(?:(?:(?^:(0x)?([0-9A-Fa-f]{4}):(0x)?([0-9A-Fa-f]{4})))|(?:(?^:(\\d+)\\-(\\d+(\\.\\d+)*)))|[Ss][Pp][Ii][Cc][Ee]))",
          "type": "string"
        },
        "mapping": {
          "description": "The ID of a cluster wide mapping. Either this or the default-key 'host' must be set.",
          "format": "pve-configid",
          "format_description": "mapping-id",
          "optional": 1,
          "type": "string"
        },
        "usb3": {
          "default": 0,
          "description": "Specifies whether if given host option is a USB3 device or port. For modern guests (machine version >= 7.1 and ostype l26 and windows > 7), this flag is irrelevant (all devices are plugged into a xhci controller).",
          "optional": 1,
          "type": "boolean"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "vcpus": {
      "default": 0,
      "description": "Number of hotplugged vcpus.",
      "minimum": 1,
      "optional": 1,
      "type": "integer"
    },
    "vga": {
      "description": "Configure the VGA hardware.",
      "format": {
        "clipboard": {
          "description": "Enable a specific clipboard. If not set, depending on the display type the SPICE one will be added. Live migration with a VNC clipboard is not possible with QEMU machine version < 10.1.",
          "enum": [
            "vnc"
          ],
          "optional": 1,
          "type": "string"
        },
        "memory": {
          "description": "Sets the VGA memory (in MiB). Has no effect with serial display.",
          "maximum": 512,
          "minimum": 4,
          "optional": 1,
          "type": "integer"
        },
        "type": {
          "default": "std",
          "default_key": 1,
          "description": "Select the VGA type. Using type 'cirrus' is not recommended.",
          "enum": [
            "cirrus",
            "qxl",
            "qxl2",
            "qxl3",
            "qxl4",
            "none",
            "serial0",
            "serial1",
            "serial2",
            "serial3",
            "std",
            "virtio",
            "virtio-gl",
            "vmware"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string",
      "verbose_description": "Configure the VGA Hardware. If you want to use high resolution modes (>= 1280x1024x16) you may need to increase the vga memory option. Since QEMU 2.9 the default VGA display type is 'std' for all OS types besides some Windows versions (XP and older) which use 'cirrus'. The 'qxl' option enables the SPICE display server. For win* OS you can select how many independent displays you want, Linux guests can add displays them self.\nYou can also run without any graphic card, using a serial device as terminal."
    },
    "virtio[n]": {
      "description": "Use volume as VIRTIO hard disk (n is 0 to 15).",
      "format": {
        "aio": {
          "description": "AIO type to use.",
          "enum": [
            "native",
            "threads",
            "io_uring"
          ],
          "optional": 1,
          "type": "string"
        },
        "backup": {
          "description": "Whether the drive should be included when making backups.",
          "optional": 1,
          "type": "boolean"
        },
        "bps": {
          "description": "Maximum r/w speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_rd": {
          "description": "Maximum read speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_rd_length": {
          "alias": "bps_rd_max_length"
        },
        "bps_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "bps_wr": {
          "description": "Maximum write speed in bytes per second.",
          "format_description": "bps",
          "optional": 1,
          "type": "integer"
        },
        "bps_wr_length": {
          "alias": "bps_wr_max_length"
        },
        "bps_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "cache": {
          "description": "The drive's cache mode",
          "enum": [
            "none",
            "writethrough",
            "writeback",
            "unsafe",
            "directsync"
          ],
          "optional": 1,
          "type": "string"
        },
        "detect_zeroes": {
          "description": "Controls whether to detect and try to optimize writes of zeroes.",
          "optional": 1,
          "type": "boolean"
        },
        "discard": {
          "description": "Controls whether to pass discard/trim requests to the underlying storage.",
          "enum": [
            "ignore",
            "on"
          ],
          "optional": 1,
          "type": "string"
        },
        "file": {
          "default_key": 1,
          "description": "The drive's backing volume.",
          "format": "pve-volume-id-or-qm-path",
          "format_description": "volume",
          "type": "string"
        },
        "format": {
          "description": "The drive's backing file's data format.",
          "enum": [
            "raw",
            "qcow",
            "qed",
            "qcow2",
            "vmdk",
            "cloop"
          ],
          "optional": 1,
          "type": "string"
        },
        "iops": {
          "description": "Maximum r/w I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max": {
          "description": "Maximum unthrottled r/w I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_max_length": {
          "description": "Maximum length of I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_rd": {
          "description": "Maximum read I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_length": {
          "alias": "iops_rd_max_length"
        },
        "iops_rd_max": {
          "description": "Maximum unthrottled read I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_rd_max_length": {
          "description": "Maximum length of read I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iops_wr": {
          "description": "Maximum write I/O in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_length": {
          "alias": "iops_wr_max_length"
        },
        "iops_wr_max": {
          "description": "Maximum unthrottled write I/O pool in operations per second.",
          "format_description": "iops",
          "optional": 1,
          "type": "integer"
        },
        "iops_wr_max_length": {
          "description": "Maximum length of write I/O bursts in seconds.",
          "format_description": "seconds",
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "iothread": {
          "description": "Whether to use iothreads for this drive",
          "optional": 1,
          "type": "boolean"
        },
        "mbps": {
          "description": "Maximum r/w speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_max": {
          "description": "Maximum unthrottled r/w pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd": {
          "description": "Maximum read speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_rd_max": {
          "description": "Maximum unthrottled read pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr": {
          "description": "Maximum write speed in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "mbps_wr_max": {
          "description": "Maximum unthrottled write pool in megabytes per second.",
          "format_description": "mbps",
          "optional": 1,
          "type": "number"
        },
        "media": {
          "default": "disk",
          "description": "The drive's media type.",
          "enum": [
            "cdrom",
            "disk"
          ],
          "optional": 1,
          "type": "string"
        },
        "replicate": {
          "default": 1,
          "description": "Whether the drive should considered for replication jobs.",
          "optional": 1,
          "type": "boolean"
        },
        "rerror": {
          "description": "Read error action.",
          "enum": [
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        },
        "ro": {
          "description": "Whether the drive is read-only.",
          "optional": 1,
          "type": "boolean"
        },
        "serial": {
          "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
          "format": "urlencoded",
          "format_description": "serial",
          "maxLength": 60,
          "optional": 1,
          "type": "string"
        },
        "shared": {
          "default": 0,
          "description": "Mark this locally-managed volume as available on all nodes",
          "optional": 1,
          "type": "boolean",
          "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
        },
        "size": {
          "description": "Disk size. This is purely informational and has no effect.",
          "format": "disk-size",
          "format_description": "DiskSize",
          "optional": 1,
          "type": "string"
        },
        "snapshot": {
          "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
          "optional": 1,
          "type": "boolean"
        },
        "volume": {
          "alias": "file"
        },
        "werror": {
          "description": "Write error action.",
          "enum": [
            "enospc",
            "ignore",
            "report",
            "stop"
          ],
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "virtiofs[n]": {
      "description": "Configuration for sharing a directory between host and guest using Virtio-fs.",
      "format": {
        "cache": {
          "default": "auto",
          "description": "The caching policy the file system should use (auto, always, metadata, never).",
          "enum": [
            "auto",
            "always",
            "metadata",
            "never"
          ],
          "optional": 1,
          "type": "string"
        },
        "direct-io": {
          "default": 0,
          "description": "Honor the O_DIRECT flag passed down by guest applications.",
          "optional": 1,
          "type": "boolean"
        },
        "dirid": {
          "default_key": 1,
          "description": "Mapping identifier of the directory mapping to be shared with the guest. Also used as a mount tag inside the VM.",
          "format": "pve-configid",
          "format_description": "mapping-id",
          "type": "string"
        },
        "expose-acl": {
          "default": 0,
          "description": "Enable support for POSIX ACLs (enabled ACL implies xattr) for this mount.",
          "optional": 1,
          "type": "boolean"
        },
        "expose-xattr": {
          "default": 0,
          "description": "Enable support for extended attributes for this mount.",
          "optional": 1,
          "type": "boolean"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "vmgenid": {
      "default": "1 (autogenerated)",
      "description": "Set VM Generation ID. Use '1' to autogenerate on create or update, pass '0' to disable explicitly.",
      "format_description": "UUID",
      "optional": 1,
      "pattern": "(?:[a-fA-F0-9]{8}(?:-[a-fA-F0-9]{4}){3}-[a-fA-F0-9]{12}|[01])",
      "type": "string",
      "verbose_description": "The VM generation ID (vmgenid) device exposes a 128-bit integer value identifier to the guest OS. This allows to notify the guest operating system when the virtual machine is executed with a different configuration (e.g. snapshot execution or creation from a template). The guest operating system notices the change, and is then able to react as appropriate by marking its copies of distributed databases as dirty, re-initializing its random number generator, etc.\nNote that auto-creation only works when done through API/CLI create or update methods, but not when manually editing the config file."
    },
    "vmstate": {
      "description": "Reference to a volume which stores the VM state. This is used internally for snapshots.",
      "format": "pve-volume-id",
      "optional": 1,
      "type": "string"
    },
    "vmstatestorage": {
      "description": "Default storage for VM state volumes/files.",
      "format": "pve-storage-id",
      "format_description": "storage ID",
      "optional": 1,
      "type": "string"
    },
    "watchdog": {
      "description": "Create a virtual hardware watchdog device.",
      "format": "pve-qm-watchdog",
      "optional": 1,
      "type": "string",
      "verbose_description": "Create a virtual hardware watchdog device. Once enabled (by a guest action), the watchdog must be periodically polled by an agent inside the guest or else the watchdog will reset the guest (or execute the respective action specified)"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get the virtual machine configuration with pending configuration changes applied. Set the 'current' parameter to get the current configuration instead.",
  "method": "GET",
  "name": "vm_config",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "current": {
        "default": 0,
        "description": "Get current values (instead of pending values).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "snapshot": {
        "description": "Fetch config values from given snapshot.",
        "format": "pve-configid",
        "maxLength": 40,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "description": "The VM configuration.",
    "properties": {
      "acpi": {
        "default": 1,
        "description": "Enable/disable ACPI.",
        "optional": 1,
        "type": "boolean"
      },
      "affinity": {
        "description": "List of host cores used to execute guest processes, for example: 0,5,8-11",
        "format": "pve-cpuset",
        "optional": 1,
        "type": "string"
      },
      "agent": {
        "description": "Enable/disable communication with the QEMU Guest Agent and its properties.",
        "format": {
          "enabled": {
            "default": 0,
            "default_key": 1,
            "description": "Enable/disable communication with a QEMU Guest Agent (QGA) running in the VM.",
            "type": "boolean"
          },
          "freeze-fs": {
            "default": 1,
            "description": "Freeze guest filesystems through QGA for consistent disk state on operations such as snapshots, backups, replications and clones.",
            "optional": 1,
            "type": "boolean",
            "verbose_description": "Whether to issue the guest-fsfreeze-freeze and guest-fsfreeze-thaw QEMU guest agent commands. Backups in snapshot mode, clones, snapshots without RAM, importing disks from a running guest, and replications normally issue a guest-fsfreeze-freeze and a respective thaw command when the QEMU Guest agent option is enabled in the guest's configuration and the agent is running inside of the guest.\n\nThe deprecated 'freeze-fs-on-backup' setting is treated as an alias for this setting."
          },
          "freeze-fs-on-backup": {
            "alias": "freeze-fs"
          },
          "fstrim_cloned_disks": {
            "default": 0,
            "description": "Run fstrim after moving a disk or migrating the VM.",
            "optional": 1,
            "type": "boolean"
          },
          "guest-fsfreeze": {
            "alias": "freeze-fs"
          },
          "type": {
            "default": "virtio",
            "description": "Select the agent type",
            "enum": [
              "virtio",
              "isa"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "allow-ksm": {
        "default": 1,
        "description": "Allow memory pages of this guest to be merged via KSM (Kernel Samepage Merging).",
        "optional": 1,
        "type": "boolean"
      },
      "amd-sev": {
        "description": "Secure Encrypted Virtualization (SEV) features by AMD CPUs",
        "format": "pve-qemu-sev-fmt",
        "optional": 1,
        "type": "string"
      },
      "arch": {
        "description": "Virtual processor architecture. Defaults to the host architecture.",
        "enum": [
          "x86_64",
          "aarch64"
        ],
        "optional": 1,
        "type": "string"
      },
      "args": {
        "description": "Arbitrary arguments passed to kvm.",
        "optional": 1,
        "type": "string",
        "verbose_description": "Arbitrary arguments passed to kvm, for example:\n\nargs: -no-reboot -smbios 'type=0,vendor=FOO'\n\nNOTE: this option is for experts only.\n"
      },
      "audio0": {
        "description": "Configure a audio device, useful in combination with QXL/Spice.",
        "format": {
          "device": {
            "description": "Configure an audio device.",
            "enum": [
              "ich9-intel-hda",
              "intel-hda",
              "AC97"
            ],
            "type": "string"
          },
          "driver": {
            "default": "spice",
            "description": "Driver backend for the audio device.",
            "enum": [
              "spice",
              "none"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "autostart": {
        "default": 0,
        "description": "Automatic restart after crash (currently ignored).",
        "optional": 1,
        "type": "boolean"
      },
      "balloon": {
        "description": "Amount of target RAM for the VM in MiB. The balloon driver is enabled by default, unless it is explicitly disabled by setting the value to zero.",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "bios": {
        "default": "seabios",
        "description": "Select BIOS implementation.",
        "enum": [
          "seabios",
          "ovmf"
        ],
        "optional": 1,
        "type": "string"
      },
      "boot": {
        "description": "Specify guest boot order. Use the 'order=' sub-property as usage with no key or 'legacy=' is deprecated.",
        "format": "pve-qm-boot",
        "optional": 1,
        "type": "string"
      },
      "bootdisk": {
        "description": "Enable booting from specified disk. Deprecated: Use 'boot: order=foo;bar' instead.",
        "format": "pve-qm-bootdisk",
        "optional": 1,
        "pattern": "(ide|sata|scsi|virtio)\\d+",
        "type": "string"
      },
      "cdrom": {
        "description": "This is an alias for option -ide2",
        "format": "pve-qm-ide",
        "optional": 1,
        "type": "string",
        "typetext": "<volume>"
      },
      "cicustom": {
        "description": "cloud-init: Specify custom files to replace the automatically generated ones at start.",
        "format": "pve-qm-cicustom",
        "optional": 1,
        "type": "string"
      },
      "cipassword": {
        "description": "cloud-init: Password to assign the user. Using this is generally not recommended. Use ssh keys instead. Also note that older cloud-init versions do not support hashed passwords.",
        "optional": 1,
        "type": "string"
      },
      "citype": {
        "description": "Specifies the cloud-init configuration format. The default depends on the configured operating system type (`ostype`. We use the `nocloud` format for Linux, and `configdrive2` for windows.",
        "enum": [
          "configdrive2",
          "nocloud",
          "opennebula"
        ],
        "optional": 1,
        "type": "string"
      },
      "ciupgrade": {
        "default": 1,
        "description": "cloud-init: do an automatic package upgrade after the first boot.",
        "optional": 1,
        "type": "boolean"
      },
      "ciuser": {
        "description": "cloud-init: User name to change ssh keys and password for instead of the image's configured default user.",
        "optional": 1,
        "type": "string"
      },
      "cores": {
        "default": 1,
        "description": "The number of cores per socket.",
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "cpu": {
        "description": "Emulated CPU type.",
        "format": "pve-vm-cpu-conf",
        "optional": 1,
        "type": "string"
      },
      "cpulimit": {
        "default": 0,
        "description": "Limit of CPU usage.",
        "maximum": 128,
        "minimum": 0,
        "optional": 1,
        "type": "number",
        "verbose_description": "Limit of CPU usage.\n\nNOTE: If the computer has 2 CPUs, it has total of '2' CPU time. Value '0' indicates no CPU limit."
      },
      "cpuunits": {
        "default": "cgroup v1: 1024, cgroup v2: 100",
        "description": "CPU weight for a VM, will be clamped to [1, 10000] in cgroup v2.",
        "maximum": 262144,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "verbose_description": "CPU weight for a VM. Argument is used in the kernel fair scheduler. The larger the number is, the more CPU time this VM gets. Number is relative to weights of all the other running VMs."
      },
      "description": {
        "description": "Description for the VM. Shown in the web-interface VM's summary. This is saved as comment inside the configuration file.",
        "maxLength": 8192,
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "SHA1 digest of configuration file. This can be used to prevent concurrent modifications.",
        "type": "string"
      },
      "efidisk0": {
        "description": "Configure a disk for storing EFI vars.",
        "format": {
          "efitype": {
            "default": "2m",
            "description": "Size and type of the OVMF EFI vars. '4m' is newer and recommended, and required for Secure Boot. For backwards compatibility, '2m' is used if not otherwise specified. Ignored for VMs with arch=aarch64 (ARM).",
            "enum": [
              "2m",
              "4m"
            ],
            "optional": 1,
            "type": "string"
          },
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id-or-qm-path",
            "format_description": "volume",
            "type": "string"
          },
          "format": {
            "description": "The drive's backing file's data format.",
            "enum": [
              "raw",
              "qcow",
              "qed",
              "qcow2",
              "vmdk",
              "cloop"
            ],
            "optional": 1,
            "type": "string"
          },
          "ms-cert": {
            "default": "2011",
            "description": "Informational marker indicating the version of the latest Microsoft UEFI certificates that have been enrolled by Proxmox VE. The value '2023k' means that the 'Microsoft UEFI CA 2023', the 'Windows UEFI CA 2023' and the 'Microsoft Corporation KEK 2K CA 2023' certificates are included. The values '2023' and '2023w' are deprecated and for compatibility only.",
            "enum": [
              "2011",
              "2023",
              "2023w",
              "2023k"
            ],
            "optional": 1,
            "type": "string"
          },
          "pre-enrolled-keys": {
            "default": 0,
            "description": "Use am EFI vars template with distribution-specific and Microsoft Standard keys enrolled, if used with 'efitype=4m'. Note that this will enable Secure Boot by default, though it can still be turned off from within the VM.",
            "optional": 1,
            "type": "boolean"
          },
          "size": {
            "description": "Disk size. This is purely informational and has no effect.",
            "format": "disk-size",
            "format_description": "DiskSize",
            "optional": 1,
            "type": "string"
          },
          "volume": {
            "alias": "file"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "freeze": {
        "description": "Freeze CPU at startup (use 'c' monitor command to start execution).",
        "optional": 1,
        "type": "boolean"
      },
      "hookscript": {
        "description": "Script that will be executed during various steps in the vms lifetime.",
        "format": "pve-volume-id",
        "optional": 1,
        "type": "string"
      },
      "hostpci[n]": {
        "description": "Map host PCI devices into guest.",
        "format": "pve-qm-hostpci",
        "optional": 1,
        "type": "string",
        "verbose_description": "Map host PCI devices into guest.\n\nNOTE: This option allows direct access to host hardware. So it is no longer\npossible to migrate such machines - use with special care.\n\nCAUTION: Experimental! User reported problems with this option.\n"
      },
      "hotplug": {
        "default": "network,disk,usb",
        "description": "Selectively enable hotplug features. This is a comma separated list of hotplug features: 'network', 'disk', 'cpu', 'memory', 'usb' and 'cloudinit'. Use '0' to disable hotplug completely. Using '1' as value is an alias for the default `network,disk,usb`. USB hotplugging is possible for guests with machine version >= 7.1 and ostype l26 or windows > 7.",
        "format": "pve-hotplug-features",
        "optional": 1,
        "type": "string"
      },
      "hugepages": {
        "description": "Enables hugepages memory.\n\nSets the size of hugepages in MiB. If the value is set to 'any' then 1 GiB hugepages will be used if possible, otherwise the size will fall back to 2 MiB.",
        "enum": [
          "any",
          "2",
          "1024"
        ],
        "optional": 1,
        "type": "string"
      },
      "ide[n]": {
        "description": "Use volume as IDE hard disk or CD-ROM (n is 0 to 3).",
        "format": {
          "aio": {
            "description": "AIO type to use.",
            "enum": [
              "native",
              "threads",
              "io_uring"
            ],
            "optional": 1,
            "type": "string"
          },
          "backup": {
            "description": "Whether the drive should be included when making backups.",
            "optional": 1,
            "type": "boolean"
          },
          "bps": {
            "description": "Maximum r/w speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_rd": {
            "description": "Maximum read speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_rd_length": {
            "alias": "bps_rd_max_length"
          },
          "bps_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_wr": {
            "description": "Maximum write speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_wr_length": {
            "alias": "bps_wr_max_length"
          },
          "bps_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "cache": {
            "description": "The drive's cache mode",
            "enum": [
              "none",
              "writethrough",
              "writeback",
              "unsafe",
              "directsync"
            ],
            "optional": 1,
            "type": "string"
          },
          "detect_zeroes": {
            "description": "Controls whether to detect and try to optimize writes of zeroes.",
            "optional": 1,
            "type": "boolean"
          },
          "discard": {
            "description": "Controls whether to pass discard/trim requests to the underlying storage.",
            "enum": [
              "ignore",
              "on"
            ],
            "optional": 1,
            "type": "string"
          },
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id-or-qm-path",
            "format_description": "volume",
            "type": "string"
          },
          "format": {
            "description": "The drive's backing file's data format.",
            "enum": [
              "raw",
              "qcow",
              "qed",
              "qcow2",
              "vmdk",
              "cloop"
            ],
            "optional": 1,
            "type": "string"
          },
          "iops": {
            "description": "Maximum r/w I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max": {
            "description": "Maximum unthrottled r/w I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_rd": {
            "description": "Maximum read I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_length": {
            "alias": "iops_rd_max_length"
          },
          "iops_rd_max": {
            "description": "Maximum unthrottled read I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_wr": {
            "description": "Maximum write I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_length": {
            "alias": "iops_wr_max_length"
          },
          "iops_wr_max": {
            "description": "Maximum unthrottled write I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "mbps": {
            "description": "Maximum r/w speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_max": {
            "description": "Maximum unthrottled r/w pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd": {
            "description": "Maximum read speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd_max": {
            "description": "Maximum unthrottled read pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr": {
            "description": "Maximum write speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr_max": {
            "description": "Maximum unthrottled write pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "media": {
            "default": "disk",
            "description": "The drive's media type.",
            "enum": [
              "cdrom",
              "disk"
            ],
            "optional": 1,
            "type": "string"
          },
          "model": {
            "description": "The drive's reported model name, url-encoded, up to 40 bytes long.",
            "format": "urlencoded",
            "format_description": "model",
            "maxLength": 120,
            "optional": 1,
            "type": "string"
          },
          "replicate": {
            "default": 1,
            "description": "Whether the drive should considered for replication jobs.",
            "optional": 1,
            "type": "boolean"
          },
          "rerror": {
            "description": "Read error action.",
            "enum": [
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "serial": {
            "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
            "format": "urlencoded",
            "format_description": "serial",
            "maxLength": 60,
            "optional": 1,
            "type": "string"
          },
          "shared": {
            "default": 0,
            "description": "Mark this locally-managed volume as available on all nodes",
            "optional": 1,
            "type": "boolean",
            "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
          },
          "size": {
            "description": "Disk size. This is purely informational and has no effect.",
            "format": "disk-size",
            "format_description": "DiskSize",
            "optional": 1,
            "type": "string"
          },
          "snapshot": {
            "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
            "optional": 1,
            "type": "boolean"
          },
          "ssd": {
            "description": "Whether to expose this drive as an SSD, rather than a rotational hard disk.",
            "optional": 1,
            "type": "boolean"
          },
          "volume": {
            "alias": "file"
          },
          "werror": {
            "description": "Write error action.",
            "enum": [
              "enospc",
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "wwn": {
            "description": "The drive's worldwide name, encoded as 16 bytes hex string, prefixed by '0x'.",
            "format_description": "wwn",
            "optional": 1,
            "pattern": "(?^:^(0x)[0-9a-fA-F]{16})",
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "intel-tdx": {
        "description": "Trusted Domain Extension (TDX) features by Intel CPUs",
        "format": "pve-qemu-tdx-fmt",
        "optional": 1,
        "type": "string"
      },
      "ipconfig[n]": {
        "description": "cloud-init: Specify IP addresses and gateways for the corresponding interface.\n\nIP addresses use CIDR notation, gateways are optional but need an IP of the same type specified.\n\nThe special string 'dhcp' can be used for IP addresses to use DHCP, in which case no explicit\ngateway should be provided.\nFor IPv6 the special string 'auto' can be used to use stateless autoconfiguration. This requires\ncloud-init 19.4 or newer.\n\nIf cloud-init is enabled and neither an IPv4 nor an IPv6 address is specified, it defaults to using\ndhcp on IPv4.\n",
        "format": "pve-qm-ipconfig",
        "optional": 1,
        "type": "string"
      },
      "ivshmem": {
        "description": "Inter-VM shared memory. Useful for direct communication between VMs, or to the host.",
        "format": {
          "name": {
            "description": "The name of the file. Will be prefixed with 'pve-shm-'. Default is the VMID. Will be deleted when the VM is stopped.",
            "format_description": "string",
            "optional": 1,
            "pattern": "[a-zA-Z0-9\\-]+",
            "type": "string"
          },
          "size": {
            "description": "The size of the file in MB.",
            "minimum": 1,
            "type": "integer"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "keephugepages": {
        "default": 0,
        "description": "Use together with hugepages. If enabled, hugepages will not not be deleted after VM shutdown and can be used for subsequent starts.",
        "optional": 1,
        "type": "boolean"
      },
      "keyboard": {
        "default": null,
        "description": "Keyboard layout for VNC server. This option is generally not required and is often better handled from within the guest OS.",
        "enum": [
          "de",
          "de-ch",
          "da",
          "en-gb",
          "en-us",
          "es",
          "fi",
          "fr",
          "fr-be",
          "fr-ca",
          "fr-ch",
          "hu",
          "is",
          "it",
          "ja",
          "lt",
          "mk",
          "nl",
          "no",
          "pl",
          "pt",
          "pt-br",
          "sv",
          "sl",
          "tr"
        ],
        "optional": 1,
        "type": "string"
      },
      "kvm": {
        "default": 1,
        "description": "Enable/disable KVM hardware virtualization.",
        "optional": 1,
        "type": "boolean"
      },
      "localtime": {
        "description": "Set the real time clock (RTC) to local time. This is enabled by default if the `ostype` indicates a Microsoft Windows OS.",
        "optional": 1,
        "type": "boolean"
      },
      "lock": {
        "description": "Lock/unlock the VM.",
        "enum": [
          "backup",
          "clone",
          "create",
          "migrate",
          "rollback",
          "snapshot",
          "snapshot-delete",
          "suspending",
          "suspended"
        ],
        "optional": 1,
        "type": "string"
      },
      "machine": {
        "description": "Specify the QEMU machine.",
        "format": {
          "aw-bits": {
            "description": "Specifies the vIOMMU address space bit width.",
            "maximum": 64,
            "minimum": 32,
            "optional": 1,
            "type": "number",
            "verbose_description": "Specifies the vIOMMU address space bit width.\n\nIntel vIOMMU supports a bit width of either 39 or 48 bits and VirtIO vIOMMU supports any bit width between 32 and 64 bits."
          },
          "enable-s3": {
            "description": "Enables S3 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
            "optional": 1,
            "type": "boolean"
          },
          "enable-s4": {
            "description": "Enables S4 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
            "optional": 1,
            "type": "boolean"
          },
          "type": {
            "default_key": 1,
            "description": "Specifies the QEMU machine type.",
            "format_description": "machine type",
            "maxLength": 40,
            "optional": 1,
            "pattern": "(pc|pc(-i440fx)?-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|q35|pc-q35-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|virt(?:-\\d+(\\.\\d+)+)?(\\+pve\\d+)?)",
            "type": "string"
          },
          "viommu": {
            "description": "Enable and set guest vIOMMU variant (Intel vIOMMU needs q35 to be set as machine type).",
            "enum": [
              "intel",
              "virtio"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "memory": {
        "description": "Memory properties.",
        "format": {
          "current": {
            "default": 512,
            "default_key": 1,
            "description": "Current amount of online RAM for the VM in MiB. This is the maximum available memory when you use the balloon device.",
            "minimum": 16,
            "type": "integer"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "meta": {
        "description": "Some (read-only) meta-information about this guest.",
        "format": {
          "creation-qemu": {
            "description": "The QEMU (machine) version from the time this VM was created.",
            "optional": 1,
            "pattern": "\\d+(\\.\\d+)+",
            "type": "string"
          },
          "ctime": {
            "description": "The guest creation timestamp as UNIX epoch time",
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "migrate_downtime": {
        "default": 0.1,
        "description": "Set maximum tolerated downtime (in seconds) for migrations. Should the migration not be able to converge in the very end, because too much newly dirtied RAM needs to be transferred, the limit will be increased automatically step-by-step until migration can converge. Will be capped to 2000 seconds (maximum in QEMU).",
        "minimum": 0,
        "optional": 1,
        "type": "number"
      },
      "migrate_speed": {
        "default": 0,
        "description": "Set maximum speed (in MB/s) for migrations. Value 0 is no limit.",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "name": {
        "description": "Set a name for the VM. Only used on the configuration web interface.",
        "format": "dns-name",
        "optional": 1,
        "type": "string"
      },
      "nameserver": {
        "description": "cloud-init: Sets DNS server IP address for a container. Create will automatically use the setting from the host if neither searchdomain nor nameserver are set.",
        "format": "address-list",
        "optional": 1,
        "type": "string"
      },
      "net[n]": {
        "description": "Specify network devices.",
        "format": {
          "bridge": {
            "description": "Bridge to attach the network device to. The Proxmox VE standard bridge\nis called 'vmbr0'.\n\nIf you do not specify a bridge, we create a kvm user (NATed) network\ndevice, which provides DHCP and DNS services. The following addresses\nare used:\n\n 10.0.2.2   Gateway\n 10.0.2.3   DNS Server\n 10.0.2.4   SMB Server\n\nThe DHCP server assign addresses to the guest starting from 10.0.2.15.\n",
            "format": "pve-bridge-id",
            "format_description": "bridge",
            "optional": 1,
            "type": "string"
          },
          "e1000": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "e1000-82540em": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "e1000-82544gc": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "e1000-82545em": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "e1000e": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "firewall": {
            "description": "Whether this interface should be protected by the firewall.",
            "optional": 1,
            "type": "boolean"
          },
          "i82551": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "i82557b": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "i82559er": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "link_down": {
            "description": "Whether this interface should be disconnected (like pulling the plug).",
            "optional": 1,
            "type": "boolean"
          },
          "macaddr": {
            "description": "MAC address. That address must be unique within your network. This is automatically generated if not specified.",
            "format": "mac-addr",
            "format_description": "XX:XX:XX:XX:XX:XX",
            "optional": 1,
            "type": "string",
            "verbose_description": "A common MAC address with the I/G (Individual/Group) bit not set."
          },
          "model": {
            "default_key": 1,
            "description": "Network Card Model. The 'virtio' model provides the best performance with very low CPU overhead. If your guest does not support this driver, it is usually best to use 'e1000'.",
            "enum": [
              "e1000",
              "e1000-82540em",
              "e1000-82544gc",
              "e1000-82545em",
              "e1000e",
              "i82551",
              "i82557b",
              "i82559er",
              "ne2k_isa",
              "ne2k_pci",
              "pcnet",
              "rtl8139",
              "virtio",
              "vmxnet3"
            ],
            "type": "string"
          },
          "mtu": {
            "description": "Force MTU of network device (VirtIO only). Setting to '1' or empty will use the bridge MTU",
            "maximum": 65520,
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "ne2k_isa": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "ne2k_pci": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "pcnet": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "queues": {
            "description": "Number of packet queues to be used on the device.",
            "maximum": 64,
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          },
          "rate": {
            "description": "Rate limit in mbps (megabytes per second) as floating point number.",
            "minimum": 0,
            "optional": 1,
            "type": "number"
          },
          "rtl8139": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "tag": {
            "description": "VLAN tag to apply to packets on this interface.",
            "maximum": 4094,
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "trunks": {
            "description": "VLAN trunks to pass through this interface.",
            "format_description": "vlanid[;vlanid...]",
            "optional": 1,
            "pattern": "(?^:\\d+(?:-\\d+)?(?:;\\d+(?:-\\d+)?)*)",
            "type": "string"
          },
          "virtio": {
            "alias": "macaddr",
            "keyAlias": "model"
          },
          "vmxnet3": {
            "alias": "macaddr",
            "keyAlias": "model"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "numa": {
        "default": 0,
        "description": "Enable/disable NUMA.",
        "optional": 1,
        "type": "boolean"
      },
      "numa[n]": {
        "description": "NUMA topology.",
        "format": {
          "cpus": {
            "description": "CPUs accessing this NUMA node.",
            "format_description": "id[-id];...",
            "pattern": "(?^:\\d+(?:-\\d+)?(?:;\\d+(?:-\\d+)?)*)",
            "type": "string"
          },
          "hostnodes": {
            "description": "Host NUMA nodes to use.",
            "format_description": "id[-id];...",
            "optional": 1,
            "pattern": "(?^:\\d+(?:-\\d+)?(?:;\\d+(?:-\\d+)?)*)",
            "type": "string"
          },
          "memory": {
            "description": "Amount of memory this NUMA node provides.",
            "optional": 1,
            "type": "number"
          },
          "policy": {
            "description": "NUMA allocation policy.",
            "enum": [
              "preferred",
              "bind",
              "interleave"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "onboot": {
        "default": 0,
        "description": "Specifies whether a VM will be started during system bootup.",
        "optional": 1,
        "type": "boolean"
      },
      "ostype": {
        "default": "other",
        "description": "Specify guest operating system.",
        "enum": [
          "other",
          "wxp",
          "w2k",
          "w2k3",
          "w2k8",
          "wvista",
          "win7",
          "win8",
          "win10",
          "win11",
          "l24",
          "l26",
          "solaris"
        ],
        "optional": 1,
        "type": "string",
        "verbose_description": "Specify guest operating system. This is used to enable special\noptimization/features for specific operating systems:\n\n[horizontal]\nother;; unspecified OS\nwxp;; Microsoft Windows XP\nw2k;; Microsoft Windows 2000\nw2k3;; Microsoft Windows 2003\nw2k8;; Microsoft Windows 2008\nwvista;; Microsoft Windows Vista\nwin7;; Microsoft Windows 7\nwin8;; Microsoft Windows 8/2012/2012r2\nwin10;; Microsoft Windows 10/2016/2019\nwin11;; Microsoft Windows 11/2022/2025\nl24;; Linux 2.4 Kernel\nl26;; Linux 2.6 - 7.X Kernel\nsolaris;; Solaris/OpenSolaris/OpenIndiania kernel\n"
      },
      "parallel[n]": {
        "description": "Map host parallel devices (n is 0 to 2).",
        "optional": 1,
        "pattern": "/dev/parport\\d+|/dev/usb/lp\\d+",
        "type": "string",
        "verbose_description": "Map host parallel devices (n is 0 to 2).\n\nNOTE: This option allows direct access to host hardware. So it is no longer possible to migrate such\nmachines - use with special care.\n\nCAUTION: Experimental! User reported problems with this option.\n"
      },
      "parent": {
        "description": "Parent snapshot name. This is used internally, and should not be modified.",
        "format": "pve-configid",
        "maxLength": 40,
        "optional": 1,
        "type": "string"
      },
      "protection": {
        "default": 0,
        "description": "Sets the protection flag of the VM. This will disable the remove VM and remove disk operations.",
        "optional": 1,
        "type": "boolean"
      },
      "reboot": {
        "default": 1,
        "description": "Allow reboot. If set to '0' the VM exit on reboot.",
        "optional": 1,
        "type": "boolean"
      },
      "rng0": {
        "description": "Configure a VirtIO-based Random Number Generator.",
        "format": "pve-qm-rng",
        "optional": 1,
        "type": "string"
      },
      "running-nets-host-mtu": {
        "description": "List of VirtIO network devices and their effective host_mtu setting. A value of 0 means that the host_mtu parameter is to be avoided for the corresponding device. This is used internally for snapshots.",
        "optional": 1,
        "pattern": "net\\d+=\\d+(,net\\d+=\\d+)*",
        "type": "string"
      },
      "runningcpu": {
        "description": "Specifies the QEMU '-cpu' parameter of the running vm. This is used internally for snapshots.",
        "format_description": "QEMU -cpu parameter",
        "optional": 1,
        "pattern": "(?^u:^((?>[+-]?[\\w\\-\\._=]+,?)+)$)",
        "type": "string"
      },
      "runningmachine": {
        "description": "Specifies the QEMU machine type of the running vm. This is used internally for snapshots.",
        "format": {
          "aw-bits": {
            "description": "Specifies the vIOMMU address space bit width.",
            "maximum": 64,
            "minimum": 32,
            "optional": 1,
            "type": "number",
            "verbose_description": "Specifies the vIOMMU address space bit width.\n\nIntel vIOMMU supports a bit width of either 39 or 48 bits and VirtIO vIOMMU supports any bit width between 32 and 64 bits."
          },
          "enable-s3": {
            "description": "Enables S3 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
            "optional": 1,
            "type": "boolean"
          },
          "enable-s4": {
            "description": "Enables S4 power state. Defaults to false beginning with machine types 9.2+pve1, true before.",
            "optional": 1,
            "type": "boolean"
          },
          "type": {
            "default_key": 1,
            "description": "Specifies the QEMU machine type.",
            "format_description": "machine type",
            "maxLength": 40,
            "optional": 1,
            "pattern": "(pc|pc(-i440fx)?-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|q35|pc-q35-\\d+(\\.\\d+)+(\\+pve\\d+)?(\\.pxe)?|virt(?:-\\d+(\\.\\d+)+)?(\\+pve\\d+)?)",
            "type": "string"
          },
          "viommu": {
            "description": "Enable and set guest vIOMMU variant (Intel vIOMMU needs q35 to be set as machine type).",
            "enum": [
              "intel",
              "virtio"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "sata[n]": {
        "description": "Use volume as SATA hard disk or CD-ROM (n is 0 to 5).",
        "format": {
          "aio": {
            "description": "AIO type to use.",
            "enum": [
              "native",
              "threads",
              "io_uring"
            ],
            "optional": 1,
            "type": "string"
          },
          "backup": {
            "description": "Whether the drive should be included when making backups.",
            "optional": 1,
            "type": "boolean"
          },
          "bps": {
            "description": "Maximum r/w speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_rd": {
            "description": "Maximum read speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_rd_length": {
            "alias": "bps_rd_max_length"
          },
          "bps_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_wr": {
            "description": "Maximum write speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_wr_length": {
            "alias": "bps_wr_max_length"
          },
          "bps_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "cache": {
            "description": "The drive's cache mode",
            "enum": [
              "none",
              "writethrough",
              "writeback",
              "unsafe",
              "directsync"
            ],
            "optional": 1,
            "type": "string"
          },
          "detect_zeroes": {
            "description": "Controls whether to detect and try to optimize writes of zeroes.",
            "optional": 1,
            "type": "boolean"
          },
          "discard": {
            "description": "Controls whether to pass discard/trim requests to the underlying storage.",
            "enum": [
              "ignore",
              "on"
            ],
            "optional": 1,
            "type": "string"
          },
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id-or-qm-path",
            "format_description": "volume",
            "type": "string"
          },
          "format": {
            "description": "The drive's backing file's data format.",
            "enum": [
              "raw",
              "qcow",
              "qed",
              "qcow2",
              "vmdk",
              "cloop"
            ],
            "optional": 1,
            "type": "string"
          },
          "iops": {
            "description": "Maximum r/w I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max": {
            "description": "Maximum unthrottled r/w I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_rd": {
            "description": "Maximum read I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_length": {
            "alias": "iops_rd_max_length"
          },
          "iops_rd_max": {
            "description": "Maximum unthrottled read I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_wr": {
            "description": "Maximum write I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_length": {
            "alias": "iops_wr_max_length"
          },
          "iops_wr_max": {
            "description": "Maximum unthrottled write I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "mbps": {
            "description": "Maximum r/w speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_max": {
            "description": "Maximum unthrottled r/w pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd": {
            "description": "Maximum read speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd_max": {
            "description": "Maximum unthrottled read pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr": {
            "description": "Maximum write speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr_max": {
            "description": "Maximum unthrottled write pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "media": {
            "default": "disk",
            "description": "The drive's media type.",
            "enum": [
              "cdrom",
              "disk"
            ],
            "optional": 1,
            "type": "string"
          },
          "replicate": {
            "default": 1,
            "description": "Whether the drive should considered for replication jobs.",
            "optional": 1,
            "type": "boolean"
          },
          "rerror": {
            "description": "Read error action.",
            "enum": [
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "serial": {
            "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
            "format": "urlencoded",
            "format_description": "serial",
            "maxLength": 60,
            "optional": 1,
            "type": "string"
          },
          "shared": {
            "default": 0,
            "description": "Mark this locally-managed volume as available on all nodes",
            "optional": 1,
            "type": "boolean",
            "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
          },
          "size": {
            "description": "Disk size. This is purely informational and has no effect.",
            "format": "disk-size",
            "format_description": "DiskSize",
            "optional": 1,
            "type": "string"
          },
          "snapshot": {
            "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
            "optional": 1,
            "type": "boolean"
          },
          "ssd": {
            "description": "Whether to expose this drive as an SSD, rather than a rotational hard disk.",
            "optional": 1,
            "type": "boolean"
          },
          "volume": {
            "alias": "file"
          },
          "werror": {
            "description": "Write error action.",
            "enum": [
              "enospc",
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "wwn": {
            "description": "The drive's worldwide name, encoded as 16 bytes hex string, prefixed by '0x'.",
            "format_description": "wwn",
            "optional": 1,
            "pattern": "(?^:^(0x)[0-9a-fA-F]{16})",
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "scsi[n]": {
        "description": "Use volume as SCSI hard disk or CD-ROM (n is 0 to 30).",
        "format": {
          "aio": {
            "description": "AIO type to use.",
            "enum": [
              "native",
              "threads",
              "io_uring"
            ],
            "optional": 1,
            "type": "string"
          },
          "backup": {
            "description": "Whether the drive should be included when making backups.",
            "optional": 1,
            "type": "boolean"
          },
          "bps": {
            "description": "Maximum r/w speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_rd": {
            "description": "Maximum read speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_rd_length": {
            "alias": "bps_rd_max_length"
          },
          "bps_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_wr": {
            "description": "Maximum write speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_wr_length": {
            "alias": "bps_wr_max_length"
          },
          "bps_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "cache": {
            "description": "The drive's cache mode",
            "enum": [
              "none",
              "writethrough",
              "writeback",
              "unsafe",
              "directsync"
            ],
            "optional": 1,
            "type": "string"
          },
          "detect_zeroes": {
            "description": "Controls whether to detect and try to optimize writes of zeroes.",
            "optional": 1,
            "type": "boolean"
          },
          "discard": {
            "description": "Controls whether to pass discard/trim requests to the underlying storage.",
            "enum": [
              "ignore",
              "on"
            ],
            "optional": 1,
            "type": "string"
          },
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id-or-qm-path",
            "format_description": "volume",
            "type": "string"
          },
          "format": {
            "description": "The drive's backing file's data format.",
            "enum": [
              "raw",
              "qcow",
              "qed",
              "qcow2",
              "vmdk",
              "cloop"
            ],
            "optional": 1,
            "type": "string"
          },
          "iops": {
            "description": "Maximum r/w I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max": {
            "description": "Maximum unthrottled r/w I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_rd": {
            "description": "Maximum read I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_length": {
            "alias": "iops_rd_max_length"
          },
          "iops_rd_max": {
            "description": "Maximum unthrottled read I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_wr": {
            "description": "Maximum write I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_length": {
            "alias": "iops_wr_max_length"
          },
          "iops_wr_max": {
            "description": "Maximum unthrottled write I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iothread": {
            "description": "Whether to use iothreads for this drive",
            "optional": 1,
            "type": "boolean"
          },
          "mbps": {
            "description": "Maximum r/w speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_max": {
            "description": "Maximum unthrottled r/w pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd": {
            "description": "Maximum read speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd_max": {
            "description": "Maximum unthrottled read pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr": {
            "description": "Maximum write speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr_max": {
            "description": "Maximum unthrottled write pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "media": {
            "default": "disk",
            "description": "The drive's media type.",
            "enum": [
              "cdrom",
              "disk"
            ],
            "optional": 1,
            "type": "string"
          },
          "product": {
            "description": "The drive's product name, up to 16 bytes long.",
            "format_description": "product",
            "optional": 1,
            "pattern": "[A-Za-z0-9\\-_\\s]{,16}",
            "type": "string"
          },
          "queues": {
            "description": "Number of queues.",
            "minimum": 2,
            "optional": 1,
            "type": "integer"
          },
          "replicate": {
            "default": 1,
            "description": "Whether the drive should considered for replication jobs.",
            "optional": 1,
            "type": "boolean"
          },
          "rerror": {
            "description": "Read error action.",
            "enum": [
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "ro": {
            "description": "Whether the drive is read-only.",
            "optional": 1,
            "type": "boolean"
          },
          "scsiblock": {
            "default": 0,
            "description": "whether to use scsi-block for full passthrough of host block device\n\nWARNING: can lead to I/O errors in combination with low memory or high memory fragmentation on host",
            "optional": 1,
            "type": "boolean"
          },
          "serial": {
            "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
            "format": "urlencoded",
            "format_description": "serial",
            "maxLength": 60,
            "optional": 1,
            "type": "string"
          },
          "shared": {
            "default": 0,
            "description": "Mark this locally-managed volume as available on all nodes",
            "optional": 1,
            "type": "boolean",
            "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
          },
          "size": {
            "description": "Disk size. This is purely informational and has no effect.",
            "format": "disk-size",
            "format_description": "DiskSize",
            "optional": 1,
            "type": "string"
          },
          "snapshot": {
            "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
            "optional": 1,
            "type": "boolean"
          },
          "ssd": {
            "description": "Whether to expose this drive as an SSD, rather than a rotational hard disk.",
            "optional": 1,
            "type": "boolean"
          },
          "vendor": {
            "description": "The drive's vendor name, up to 8 bytes long.",
            "format_description": "vendor",
            "optional": 1,
            "pattern": "[A-Za-z0-9\\-_\\s]{,8}",
            "type": "string"
          },
          "volume": {
            "alias": "file"
          },
          "werror": {
            "description": "Write error action.",
            "enum": [
              "enospc",
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "wwn": {
            "description": "The drive's worldwide name, encoded as 16 bytes hex string, prefixed by '0x'.",
            "format_description": "wwn",
            "optional": 1,
            "pattern": "(?^:^(0x)[0-9a-fA-F]{16})",
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "scsihw": {
        "default": "lsi",
        "description": "SCSI controller model",
        "enum": [
          "lsi",
          "lsi53c810",
          "virtio-scsi-pci",
          "virtio-scsi-single",
          "megasas",
          "pvscsi"
        ],
        "optional": 1,
        "type": "string"
      },
      "searchdomain": {
        "description": "cloud-init: Sets DNS search domains for a container. Create will automatically use the setting from the host if neither searchdomain nor nameserver are set.",
        "optional": 1,
        "type": "string"
      },
      "serial[n]": {
        "description": "Create a serial device inside the VM (n is 0 to 3)",
        "optional": 1,
        "pattern": "(/dev/[^,]+|socket)",
        "type": "string",
        "verbose_description": "Create a serial device inside the VM (n is 0 to 3), and pass through a\nhost serial device (i.e. /dev/ttyS0), or create a unix socket on the\nhost side (use 'qm terminal' to open a terminal connection).\n\nNOTE: If you pass through a host serial device, it is no longer possible to migrate such machines -\nuse with special care.\n\nCAUTION: Experimental! User reported problems with this option.\n"
      },
      "shares": {
        "default": 1000,
        "description": "Amount of memory shares for auto-ballooning. The larger the number is, the more memory this VM gets. Number is relative to weights of all other running VMs. Using zero disables auto-ballooning. Auto-ballooning is done by pvestatd.",
        "maximum": 50000,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "smbios1": {
        "description": "Specify SMBIOS type 1 fields.",
        "format": "pve-qm-smbios1",
        "maxLength": 512,
        "optional": 1,
        "type": "string"
      },
      "smp": {
        "default": 1,
        "description": "The number of CPUs. Please use option -sockets instead.",
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "snaptime": {
        "description": "Timestamp for snapshots.",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "sockets": {
        "default": 1,
        "description": "The number of CPU sockets.",
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "spice_enhancements": {
        "description": "Configure additional enhancements for SPICE.",
        "format": {
          "foldersharing": {
            "default": "0",
            "description": "Enable folder sharing via SPICE. Needs Spice-WebDAV daemon installed in the VM.",
            "optional": 1,
            "type": "boolean"
          },
          "videostreaming": {
            "default": "off",
            "description": "Enable video streaming. Uses compression for detected video streams.",
            "enum": [
              "off",
              "all",
              "filter"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "sshkeys": {
        "description": "cloud-init: Setup public SSH keys (one key per line, OpenSSH format).",
        "format": "urlencoded",
        "optional": 1,
        "type": "string"
      },
      "startdate": {
        "default": "now",
        "description": "Set the initial date of the real time clock. Valid format for date are:'now' or '2006-06-17T16:01:21' or '2006-06-17'.",
        "optional": 1,
        "pattern": "(now|\\d{4}-\\d{1,2}-\\d{1,2}(T\\d{1,2}:\\d{1,2}:\\d{1,2})?)",
        "type": "string",
        "typetext": "(now | YYYY-MM-DD | YYYY-MM-DDTHH:MM:SS)"
      },
      "startup": {
        "description": "Startup and shutdown behavior. Order is a non-negative number defining the general startup order. Shutdown in done with reverse ordering. Additionally you can set the 'up' or 'down' delay in seconds, which specifies a delay to wait before the next VM is started or stopped.",
        "format": "pve-startup-order",
        "optional": 1,
        "type": "string",
        "typetext": "[[order=]\\d+] [,up=\\d+] [,down=\\d+] "
      },
      "tablet": {
        "default": 1,
        "description": "Enable/disable the USB tablet device.",
        "optional": 1,
        "type": "boolean",
        "verbose_description": "Enable/disable the USB tablet device. This device is usually needed to allow absolute mouse positioning with VNC. Else the mouse runs out of sync with normal VNC clients. If you're running lots of console-only guests on one host, you may consider disabling this to save some context switches. This is turned off by default if you use spice (`qm set <vmid> --vga qxl`)."
      },
      "tags": {
        "description": "Tags of the VM. This is only meta information.",
        "format": "pve-tag-list",
        "optional": 1,
        "type": "string"
      },
      "tdf": {
        "default": 0,
        "description": "Enable/disable time drift fix.",
        "optional": 1,
        "type": "boolean"
      },
      "template": {
        "default": 0,
        "description": "Enable/disable Template.",
        "optional": 1,
        "type": "boolean"
      },
      "tpmstate0": {
        "description": "Configure a Disk for storing TPM state. The format is fixed to 'raw'.",
        "format": {
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id-or-qm-path",
            "format_description": "volume",
            "type": "string"
          },
          "format": {
            "description": "Format of the image.",
            "enum": [
              "raw",
              "qcow2",
              "vmdk"
            ],
            "optional": 1,
            "type": "string"
          },
          "size": {
            "description": "Disk size. This is purely informational and has no effect.",
            "format": "disk-size",
            "format_description": "DiskSize",
            "optional": 1,
            "type": "string"
          },
          "version": {
            "default": "v1.2",
            "description": "The TPM interface version. v2.0 is newer and should be preferred. Note that this cannot be changed later on.",
            "enum": [
              "v1.2",
              "v2.0"
            ],
            "optional": 1,
            "type": "string"
          },
          "volume": {
            "alias": "file"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "unused[n]": {
        "description": "Reference to unused volumes. This is used internally, and should not be modified manually.",
        "format": {
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id",
            "format_description": "volume",
            "type": "string"
          },
          "volume": {
            "alias": "file"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "usb[n]": {
        "description": "Configure an USB device (n is 0 to 4, for machine version >= 7.1 and ostype l26 or windows > 7, n can be up to 14).",
        "format": {
          "host": {
            "default_key": 1,
            "description": "The Host USB device or port or the value 'spice'. HOSTUSBDEVICE syntax is:\n\n 'bus-port(.port)*' (decimal numbers) or\n 'vendor_id:product_id' (hexadecimal numbers) or\n 'spice'\n\nYou can use the 'lsusb -t' command to list existing usb devices.\n\nNOTE: This option allows direct access to host hardware. So it is no longer possible to migrate such\nmachines - use with special care.\n\nThe value 'spice' can be used to add a usb redirection devices for spice.\n\nEither this or the 'mapping' key must be set.\n",
            "format_description": "HOSTUSBDEVICE|spice",
            "optional": 1,
            "pattern": "(?^:(?:(?:(?^:(0x)?([0-9A-Fa-f]{4}):(0x)?([0-9A-Fa-f]{4})))|(?:(?^:(\\d+)\\-(\\d+(\\.\\d+)*)))|[Ss][Pp][Ii][Cc][Ee]))",
            "type": "string"
          },
          "mapping": {
            "description": "The ID of a cluster wide mapping. Either this or the default-key 'host' must be set.",
            "format": "pve-configid",
            "format_description": "mapping-id",
            "optional": 1,
            "type": "string"
          },
          "usb3": {
            "default": 0,
            "description": "Specifies whether if given host option is a USB3 device or port. For modern guests (machine version >= 7.1 and ostype l26 and windows > 7), this flag is irrelevant (all devices are plugged into a xhci controller).",
            "optional": 1,
            "type": "boolean"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "vcpus": {
        "default": 0,
        "description": "Number of hotplugged vcpus.",
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "vga": {
        "description": "Configure the VGA hardware.",
        "format": {
          "clipboard": {
            "description": "Enable a specific clipboard. If not set, depending on the display type the SPICE one will be added. Live migration with a VNC clipboard is not possible with QEMU machine version < 10.1.",
            "enum": [
              "vnc"
            ],
            "optional": 1,
            "type": "string"
          },
          "memory": {
            "description": "Sets the VGA memory (in MiB). Has no effect with serial display.",
            "maximum": 512,
            "minimum": 4,
            "optional": 1,
            "type": "integer"
          },
          "type": {
            "default": "std",
            "default_key": 1,
            "description": "Select the VGA type. Using type 'cirrus' is not recommended.",
            "enum": [
              "cirrus",
              "qxl",
              "qxl2",
              "qxl3",
              "qxl4",
              "none",
              "serial0",
              "serial1",
              "serial2",
              "serial3",
              "std",
              "virtio",
              "virtio-gl",
              "vmware"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "verbose_description": "Configure the VGA Hardware. If you want to use high resolution modes (>= 1280x1024x16) you may need to increase the vga memory option. Since QEMU 2.9 the default VGA display type is 'std' for all OS types besides some Windows versions (XP and older) which use 'cirrus'. The 'qxl' option enables the SPICE display server. For win* OS you can select how many independent displays you want, Linux guests can add displays them self.\nYou can also run without any graphic card, using a serial device as terminal."
      },
      "virtio[n]": {
        "description": "Use volume as VIRTIO hard disk (n is 0 to 15).",
        "format": {
          "aio": {
            "description": "AIO type to use.",
            "enum": [
              "native",
              "threads",
              "io_uring"
            ],
            "optional": 1,
            "type": "string"
          },
          "backup": {
            "description": "Whether the drive should be included when making backups.",
            "optional": 1,
            "type": "boolean"
          },
          "bps": {
            "description": "Maximum r/w speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_rd": {
            "description": "Maximum read speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_rd_length": {
            "alias": "bps_rd_max_length"
          },
          "bps_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "bps_wr": {
            "description": "Maximum write speed in bytes per second.",
            "format_description": "bps",
            "optional": 1,
            "type": "integer"
          },
          "bps_wr_length": {
            "alias": "bps_wr_max_length"
          },
          "bps_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "cache": {
            "description": "The drive's cache mode",
            "enum": [
              "none",
              "writethrough",
              "writeback",
              "unsafe",
              "directsync"
            ],
            "optional": 1,
            "type": "string"
          },
          "detect_zeroes": {
            "description": "Controls whether to detect and try to optimize writes of zeroes.",
            "optional": 1,
            "type": "boolean"
          },
          "discard": {
            "description": "Controls whether to pass discard/trim requests to the underlying storage.",
            "enum": [
              "ignore",
              "on"
            ],
            "optional": 1,
            "type": "string"
          },
          "file": {
            "default_key": 1,
            "description": "The drive's backing volume.",
            "format": "pve-volume-id-or-qm-path",
            "format_description": "volume",
            "type": "string"
          },
          "format": {
            "description": "The drive's backing file's data format.",
            "enum": [
              "raw",
              "qcow",
              "qed",
              "qcow2",
              "vmdk",
              "cloop"
            ],
            "optional": 1,
            "type": "string"
          },
          "iops": {
            "description": "Maximum r/w I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max": {
            "description": "Maximum unthrottled r/w I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_max_length": {
            "description": "Maximum length of I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_rd": {
            "description": "Maximum read I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_length": {
            "alias": "iops_rd_max_length"
          },
          "iops_rd_max": {
            "description": "Maximum unthrottled read I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_rd_max_length": {
            "description": "Maximum length of read I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iops_wr": {
            "description": "Maximum write I/O in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_length": {
            "alias": "iops_wr_max_length"
          },
          "iops_wr_max": {
            "description": "Maximum unthrottled write I/O pool in operations per second.",
            "format_description": "iops",
            "optional": 1,
            "type": "integer"
          },
          "iops_wr_max_length": {
            "description": "Maximum length of write I/O bursts in seconds.",
            "format_description": "seconds",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "iothread": {
            "description": "Whether to use iothreads for this drive",
            "optional": 1,
            "type": "boolean"
          },
          "mbps": {
            "description": "Maximum r/w speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_max": {
            "description": "Maximum unthrottled r/w pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd": {
            "description": "Maximum read speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_rd_max": {
            "description": "Maximum unthrottled read pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr": {
            "description": "Maximum write speed in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "mbps_wr_max": {
            "description": "Maximum unthrottled write pool in megabytes per second.",
            "format_description": "mbps",
            "optional": 1,
            "type": "number"
          },
          "media": {
            "default": "disk",
            "description": "The drive's media type.",
            "enum": [
              "cdrom",
              "disk"
            ],
            "optional": 1,
            "type": "string"
          },
          "replicate": {
            "default": 1,
            "description": "Whether the drive should considered for replication jobs.",
            "optional": 1,
            "type": "boolean"
          },
          "rerror": {
            "description": "Read error action.",
            "enum": [
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          },
          "ro": {
            "description": "Whether the drive is read-only.",
            "optional": 1,
            "type": "boolean"
          },
          "serial": {
            "description": "The drive's reported serial number, url-encoded, up to 20 bytes long.",
            "format": "urlencoded",
            "format_description": "serial",
            "maxLength": 60,
            "optional": 1,
            "type": "string"
          },
          "shared": {
            "default": 0,
            "description": "Mark this locally-managed volume as available on all nodes",
            "optional": 1,
            "type": "boolean",
            "verbose_description": "Mark this locally-managed volume as available on all nodes.\n\nWARNING: This option does not share the volume automatically, it assumes it is shared already!"
          },
          "size": {
            "description": "Disk size. This is purely informational and has no effect.",
            "format": "disk-size",
            "format_description": "DiskSize",
            "optional": 1,
            "type": "string"
          },
          "snapshot": {
            "description": "Controls qemu's snapshot mode feature. If activated, changes made to the disk are temporary and will be discarded when the VM is shutdown.",
            "optional": 1,
            "type": "boolean"
          },
          "volume": {
            "alias": "file"
          },
          "werror": {
            "description": "Write error action.",
            "enum": [
              "enospc",
              "ignore",
              "report",
              "stop"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "virtiofs[n]": {
        "description": "Configuration for sharing a directory between host and guest using Virtio-fs.",
        "format": {
          "cache": {
            "default": "auto",
            "description": "The caching policy the file system should use (auto, always, metadata, never).",
            "enum": [
              "auto",
              "always",
              "metadata",
              "never"
            ],
            "optional": 1,
            "type": "string"
          },
          "direct-io": {
            "default": 0,
            "description": "Honor the O_DIRECT flag passed down by guest applications.",
            "optional": 1,
            "type": "boolean"
          },
          "dirid": {
            "default_key": 1,
            "description": "Mapping identifier of the directory mapping to be shared with the guest. Also used as a mount tag inside the VM.",
            "format": "pve-configid",
            "format_description": "mapping-id",
            "type": "string"
          },
          "expose-acl": {
            "default": 0,
            "description": "Enable support for POSIX ACLs (enabled ACL implies xattr) for this mount.",
            "optional": 1,
            "type": "boolean"
          },
          "expose-xattr": {
            "default": 0,
            "description": "Enable support for extended attributes for this mount.",
            "optional": 1,
            "type": "boolean"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "vmgenid": {
        "default": "1 (autogenerated)",
        "description": "Set VM Generation ID. Use '1' to autogenerate on create or update, pass '0' to disable explicitly.",
        "format_description": "UUID",
        "optional": 1,
        "pattern": "(?:[a-fA-F0-9]{8}(?:-[a-fA-F0-9]{4}){3}-[a-fA-F0-9]{12}|[01])",
        "type": "string",
        "verbose_description": "The VM generation ID (vmgenid) device exposes a 128-bit integer value identifier to the guest OS. This allows to notify the guest operating system when the virtual machine is executed with a different configuration (e.g. snapshot execution or creation from a template). The guest operating system notices the change, and is then able to react as appropriate by marking its copies of distributed databases as dirty, re-initializing its random number generator, etc.\nNote that auto-creation only works when done through API/CLI create or update methods, but not when manually editing the config file."
      },
      "vmstate": {
        "description": "Reference to a volume which stores the VM state. This is used internally for snapshots.",
        "format": "pve-volume-id",
        "optional": 1,
        "type": "string"
      },
      "vmstatestorage": {
        "description": "Default storage for VM state volumes/files.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string"
      },
      "watchdog": {
        "description": "Create a virtual hardware watchdog device.",
        "format": "pve-qm-watchdog",
        "optional": 1,
        "type": "string",
        "verbose_description": "Create a virtual hardware watchdog device. Once enabled (by a guest action), the watchdog must be periodically polled by an agent inside the guest or else the watchdog will reset the guest (or execute the respective action specified)"
      }
    },
    "type": "object"
  }
}
```
