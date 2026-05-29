# POST /nodes/{node}/qemu/{vmid}/status/start

Start virtual machine.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force-cpu | string | no | Override QEMU's -cpu argument with the given string. |
| machine | string | no | Specify the QEMU machine. |
| migratedfrom | string | no | The cluster node name. |
| migration_network | string | no | CIDR of the (sub) network that is used for migration. |
| migration_type | string | no | Migration traffic is encrypted using an SSH tunnel by default. On secure, completely private networks this can be disabled to increase performance. |
| nets-host-mtu | string | no | Used for migration compat. List of VirtIO network devices and their effective host_mtu setting according to the QEMU object model on the source side of the migration. A value of 0 means that the host_mtu parameter is to be avoided for the corresponding device. |
| skiplock | boolean | no | Ignore locks - only root is allowed to use this option. |
| stateuri | string | no | Some command save/restore state from this location. |
| targetstorage | string | no | Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself. |
| timeout | integer | no | Wait maximal timeout seconds. |
| with-conntrack-state | boolean | no | Whether to migrate conntrack entries for running VMs. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.PowerMgmt"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Start virtual machine.",
  "method": "POST",
  "name": "vm_start",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force-cpu": {
        "description": "Override QEMU's -cpu argument with the given string.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
        "type": "string",
        "typetext": "[[type=]<machine type>] [,aw-bits=<number>] [,enable-s3=<1|0>] [,enable-s4=<1|0>] [,viommu=<intel|virtio>]"
      },
      "migratedfrom": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "migration_network": {
        "description": "CIDR of the (sub) network that is used for migration.",
        "format": "CIDR",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "migration_type": {
        "description": "Migration traffic is encrypted using an SSH tunnel by default. On secure, completely private networks this can be disabled to increase performance.",
        "enum": [
          "secure",
          "insecure"
        ],
        "optional": 1,
        "type": "string"
      },
      "nets-host-mtu": {
        "description": "Used for migration compat. List of VirtIO network devices and their effective host_mtu setting according to the QEMU object model on the source side of the migration. A value of 0 means that the host_mtu parameter is to be avoided for the corresponding device.",
        "optional": 1,
        "pattern": "net\\d+=\\d+(,net\\d+=\\d+)*",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "skiplock": {
        "description": "Ignore locks - only root is allowed to use this option.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "stateuri": {
        "description": "Some command save/restore state from this location.",
        "maxLength": 128,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "targetstorage": {
        "description": "Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself.",
        "format": "storage-pair-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "timeout": {
        "default": "max(30, vm memory in GiB)",
        "description": "Wait maximal timeout seconds.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "with-conntrack-state": {
        "default": 0,
        "description": "Whether to migrate conntrack entries for running VMs.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.PowerMgmt"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
