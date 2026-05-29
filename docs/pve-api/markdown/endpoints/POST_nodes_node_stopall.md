# POST /nodes/{node}/stopall

Stop all VMs and Containers.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force-stop | boolean | no | Force a hard-stop after the timeout. |
| max-workers | integer | no | Defines the maximum number of tasks running concurrently. If  not set, uses 'max_workers' from datacenter.cfg, and if that's not set, the available CPU threads, clamped to a maximum of 8, are used. |
| timeout | integer | no | Timeout for each guest shutdown task. Depending on `force-stop`, the shutdown gets then simply aborted or a hard-stop is forced. |
| vms | string | no | Only consider Guests with these IDs. |

## Returns

```json
{
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
  "description": "Stop all VMs and Containers.",
  "method": "POST",
  "name": "stopall",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force-stop": {
        "default": 1,
        "description": "Force a hard-stop after the timeout.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "max-workers": {
        "description": "Defines the maximum number of tasks running concurrently. If  not set, uses 'max_workers' from datacenter.cfg, and if that's not set, the available CPU threads, clamped to a maximum of 8, are used.",
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
      "timeout": {
        "default": 180,
        "description": "Timeout for each guest shutdown task. Depending on `force-stop`, the shutdown gets then simply aborted or a hard-stop is forced.",
        "maximum": 7200,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 7200)"
      },
      "vms": {
        "description": "Only consider Guests with these IDs.",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
