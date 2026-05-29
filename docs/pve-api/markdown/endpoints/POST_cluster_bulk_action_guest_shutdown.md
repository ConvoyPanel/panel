# POST /cluster/bulk-action/guest/shutdown

Bulk shutdown all guests on the cluster.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force-stop | boolean | no | Makes sure the Guest stops after the timeout. |
| max-workers | integer | no | Defines the maximum number of tasks running concurrently. |
| maxworkers | integer | no | Defines the maximum number of tasks running concurrently. Deprecated, use 'max-workers' instead. |
| timeout | integer | no | Default shutdown timeout in seconds if none is configured for the guest. |
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
  "description": "Bulk shutdown all guests on the cluster.",
  "expose_credentials": 1,
  "method": "POST",
  "name": "shutdown",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force-stop": {
        "default": 1,
        "description": "Makes sure the Guest stops after the timeout.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
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
        "default": 180,
        "description": "Default shutdown timeout in seconds if none is configured for the guest.",
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
