# POST /nodes/{node}/migrateall

Migrate all VMs and Containers.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target | string | yes | Target node. |
| max-workers | integer | no | Maximal number of parallel migration job. If not set, uses'max_workers' from datacenter.cfg. One of both must be set! |
| maxworkers | integer | no | Maximal number of parallel migration job. If not set, uses'max_workers' from datacenter.cfg. One of both must be set!Deprecated, use 'max-workers' instead. |
| vms | string | no | Only consider Guests with these IDs. |
| with-local-disks | boolean | no | Enable live storage migration for local disk |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "description": "The 'VM.Migrate' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Migrate all VMs and Containers.",
  "method": "POST",
  "name": "migrateall",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "max-workers": {
        "description": "Maximal number of parallel migration job. If not set, uses'max_workers' from datacenter.cfg. One of both must be set!",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "maxworkers": {
        "description": "Maximal number of parallel migration job. If not set, uses'max_workers' from datacenter.cfg. One of both must be set!Deprecated, use 'max-workers' instead.",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "target": {
        "description": "Target node.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vms": {
        "description": "Only consider Guests with these IDs.",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
    "description": "The 'VM.Migrate' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
