# POST /cluster/bulk-action/guest/start

Bulk start or resume all guests on the cluster.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| max-workers | integer | no | Defines the maximum number of tasks running concurrently. |
| maxworkers | integer | no | Defines the maximum number of tasks running concurrently. Deprecated, use 'max-workers' instead. |
| timeout | integer | no | Default start timeout in seconds. Only valid for VMs. (default depends on the guest configuration). |
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
  "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Bulk start or resume all guests on the cluster.",
  "expose_credentials": 1,
  "method": "POST",
  "name": "start",
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
      "timeout": {
        "description": "Default start timeout in seconds. Only valid for VMs. (default depends on the guest configuration).",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
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
    "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter.",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "description": "UPID of the worker",
    "type": "string"
  }
}
```
