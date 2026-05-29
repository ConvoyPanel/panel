# DELETE /nodes/{node}/disks/zfs/{name}

Destroy a ZFS pool.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The storage identifier. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cleanup-config | boolean | no | Marks associated storage(s) as not available on this node anymore or removes them from the configuration (if configured for this node only). |
| cleanup-disks | boolean | no | Also wipe disks so they can be repurposed afterwards. |

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
    "/",
    [
      "Sys.Modify"
    ]
  ],
  "description": "Requires additionally 'Datastore.Allocate' on /storage when setting 'cleanup-config'"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Destroy a ZFS pool.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cleanup-config": {
        "default": 0,
        "description": "Marks associated storage(s) as not available on this node anymore or removes them from the configuration (if configured for this node only).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "cleanup-disks": {
        "default": 0,
        "description": "Also wipe disks so they can be repurposed afterwards.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "name": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ],
    "description": "Requires additionally 'Datastore.Allocate' on /storage when setting 'cleanup-config'"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
