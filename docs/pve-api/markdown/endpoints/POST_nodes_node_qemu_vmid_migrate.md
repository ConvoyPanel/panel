# POST /nodes/{node}/qemu/{vmid}/migrate

Migrate virtual machine. Creates a new migration task.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target | string | yes | Target node. |
| bwlimit | integer | no | Override I/O bandwidth limit (in KiB/s). |
| force | boolean | no | Allow to migrate VMs which use local devices. Only root may use this option. |
| migration_network | string | no | CIDR of the (sub) network that is used for migration. |
| migration_type | string | no | Migration traffic is encrypted using an SSH tunnel by default. On secure, completely private networks this can be disabled to increase performance. |
| online | boolean | no | Use online/live migration if VM is running. Ignored if VM is stopped. |
| targetstorage | string | no | Mapping from source to target storages. Providing only a single storage ID maps all source storages to that storage. Providing the special value '1' will map each source storage to itself. |
| with-conntrack-state | boolean | no | Whether to migrate conntrack entries for running VMs. |
| with-local-disks | boolean | no | Enable live storage migration for local disk |

## Returns

```json
{
  "description": "the task ID.",
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
      "VM.Migrate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Migrate virtual machine. Creates a new migration task.",
  "method": "POST",
  "name": "migrate_vm",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "bwlimit": {
        "default": "migrate limit from datacenter or storage config",
        "description": "Override I/O bandwidth limit (in KiB/s).",
        "minimum": "0",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "force": {
        "description": "Allow to migrate VMs which use local devices. Only root may use this option.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "online": {
        "description": "Use online/live migration if VM is running. Ignored if VM is stopped.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "target": {
        "description": "Target node.",
        "format": "pve-node",
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
      },
      "with-local-disks": {
        "description": "Enable live storage migration for local disk",
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
        "VM.Migrate"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "the task ID.",
    "type": "string"
  }
}
```
