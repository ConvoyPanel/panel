# DELETE /nodes/{node}/storage/{storage}/prunebackups

Prune backups. Only those using the standard naming scheme are considered.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| prune-backups | string | no | Use these retention options instead of those from the storage configuration. |
| type | string | no | Either 'qemu' or 'lxc'. Only consider backups for guests of this type. |
| vmid | integer | no | Only prune backups for this VM. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "description": "You need the 'Datastore.Allocate' privilege on the storage (or if a VM ID is specified, 'Datastore.AllocateSpace' and 'VM.Backup' for the VM).",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Prune backups. Only those using the standard naming scheme are considered.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "prune-backups": {
        "description": "Use these retention options instead of those from the storage configuration.",
        "format": "prune-backups",
        "optional": 1,
        "type": "string",
        "typetext": "[keep-all=<1|0>] [,keep-daily=<N>] [,keep-hourly=<N>] [,keep-last=<N>] [,keep-monthly=<N>] [,keep-weekly=<N>] [,keep-yearly=<N>]"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "type": {
        "description": "Either 'qemu' or 'lxc'. Only consider backups for guests of this type.",
        "enum": [
          "qemu",
          "lxc"
        ],
        "optional": 1,
        "type": "string"
      },
      "vmid": {
        "description": "Only prune backups for this VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "description": "You need the 'Datastore.Allocate' privilege on the storage (or if a VM ID is specified, 'Datastore.AllocateSpace' and 'VM.Backup' for the VM).",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
