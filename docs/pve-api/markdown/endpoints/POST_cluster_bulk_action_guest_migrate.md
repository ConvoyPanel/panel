# POST /cluster/bulk-action/guest/migrate

Bulk migrate all guests on the cluster.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target | string | yes | Target node. |
| max-workers | integer | no | Defines the maximum number of tasks running concurrently. |
| maxworkers | integer | no | Defines the maximum number of tasks running concurrently. Deprecated, use 'max-workers' instead. |
| online | boolean | no | Enable live migration for VMs and restart migration for CTs. |
| vms | array | no | Only consider guests from this list of VMIDs. |
| with-local-disks | boolean | no | Enable live storage migration for local disk |

## Returns

```json
{
  "description": "UPID of the worker",
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
  "description": "Bulk migrate all guests on the cluster.",
  "expose_credentials": 1,
  "method": "POST",
  "name": "migrate",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "max-workers": {
        "default": 1,
        "description": "Defines the maximum number of tasks running concurrently.",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "maxworkers": {
        "default": 1,
        "description": "Defines the maximum number of tasks running concurrently. Deprecated, use 'max-workers' instead.",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "online": {
        "description": "Enable live migration for VMs and restart migration for CTs.",
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
      "vms": {
        "description": "Only consider guests from this list of VMIDs.",
        "items": {
          "description": "The (unique) ID of the VM.",
          "format": "pve-vmid",
          "maximum": 999999999,
          "minimum": 100,
          "type": "integer"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
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
  "returns": {
    "description": "UPID of the worker",
    "type": "string"
  }
}
```
