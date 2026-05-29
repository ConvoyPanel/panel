# POST /cluster/bulk-action/guest/suspend

Bulk suspend all guests on the cluster.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| max-workers | integer | no | Defines the maximum number of tasks running concurrently. |
| maxworkers | integer | no | Defines the maximum number of tasks running concurrently. Deprecated, use 'max-workers' instead. |
| statestorage | string | no | The storage for the VM state. |
| to-disk | boolean | no | If set, suspends the guests to disk. Will be resumed on next start. |
| vms | array | no | Only consider guests from this list of VMIDs. |

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
  "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter. Additionally, you need 'VM.Config.Disk' on the '/vms/{vmid}' path and 'Datastore.AllocateSpace' for the configured state-storage(s)",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Bulk suspend all guests on the cluster.",
  "expose_credentials": 1,
  "method": "POST",
  "name": "suspend",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "max-workers": {
        "default": 4,
        "description": "Defines the maximum number of tasks running concurrently.",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "maxworkers": {
        "default": 4,
        "description": "Defines the maximum number of tasks running concurrently. Deprecated, use 'max-workers' instead.",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "statestorage": {
        "description": "The storage for the VM state.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "requires": "to-disk",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "to-disk": {
        "default": 0,
        "description": "If set, suspends the guests to disk. Will be resumed on next start.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
      }
    }
  },
  "permissions": {
    "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter. Additionally, you need 'VM.Config.Disk' on the '/vms/{vmid}' path and 'Datastore.AllocateSpace' for the configured state-storage(s)",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "description": "UPID of the worker",
    "type": "string"
  }
}
```
