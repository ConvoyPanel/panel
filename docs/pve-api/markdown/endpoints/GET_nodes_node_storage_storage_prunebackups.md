# GET /nodes/{node}/storage/{storage}/prunebackups

Get prune information for backups. NOTE: this is only a preview and might not be what a subsequent prune call does if backups are removed/added in the meantime.

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
| vmid | integer | no | Only consider backups for this guest. |

## Returns

```json
{
  "items": {
    "properties": {
      "ctime": {
        "description": "Creation time of the backup (seconds since the UNIX epoch).",
        "type": "integer"
      },
      "mark": {
        "description": "Whether the backup would be kept or removed. Backups that are protected or don't use the standard naming scheme are not removed.",
        "enum": [
          "keep",
          "remove",
          "protected",
          "renamed"
        ],
        "type": "string"
      },
      "type": {
        "description": "One of 'qemu', 'lxc', 'openvz' or 'unknown'.",
        "type": "string"
      },
      "vmid": {
        "description": "The VM the backup belongs to.",
        "optional": 1,
        "type": "integer"
      },
      "volid": {
        "description": "Backup volume ID.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/storage/{storage}",
    [
      "Datastore.Audit",
      "Datastore.AllocateSpace"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get prune information for backups. NOTE: this is only a preview and might not be what a subsequent prune call does if backups are removed/added in the meantime.",
  "method": "GET",
  "name": "dryrun",
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
        "description": "Only consider backups for this guest.",
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
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.Audit",
        "Datastore.AllocateSpace"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "ctime": {
          "description": "Creation time of the backup (seconds since the UNIX epoch).",
          "type": "integer"
        },
        "mark": {
          "description": "Whether the backup would be kept or removed. Backups that are protected or don't use the standard naming scheme are not removed.",
          "enum": [
            "keep",
            "remove",
            "protected",
            "renamed"
          ],
          "type": "string"
        },
        "type": {
          "description": "One of 'qemu', 'lxc', 'openvz' or 'unknown'.",
          "type": "string"
        },
        "vmid": {
          "description": "The VM the backup belongs to.",
          "optional": 1,
          "type": "integer"
        },
        "volid": {
          "description": "Backup volume ID.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
