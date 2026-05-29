# POST /nodes/{node}/startall

Start all VMs and containers located on this node (by default only those with onboot=1).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force | boolean | no | Issue start command even if virtual guest have 'onboot' not set or set to off. |
| max-workers | integer | no | Defines the maximum number of tasks running concurrently. If not set, uses 'max_workers' from datacenter.cfg, and if that's not set, the available CPU threads, clamped to a maximum of 8, are used. |
| vms | string | no | Only consider guests from this comma separated list of VMIDs. |

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
  "description": "Start all VMs and containers located on this node (by default only those with onboot=1).",
  "method": "POST",
  "name": "startall",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force": {
        "default": "off",
        "description": "Issue start command even if virtual guest have 'onboot' not set or set to off.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "max-workers": {
        "description": "Defines the maximum number of tasks running concurrently. If not set, uses 'max_workers' from datacenter.cfg, and if that's not set, the available CPU threads, clamped to a maximum of 8, are used.",
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
      "vms": {
        "description": "Only consider guests from this comma separated list of VMIDs.",
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
