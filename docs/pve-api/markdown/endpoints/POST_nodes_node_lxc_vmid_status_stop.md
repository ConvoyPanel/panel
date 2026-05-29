# POST /nodes/{node}/lxc/{vmid}/status/stop

Stop the container. This will abruptly stop all processes running in the container.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| overrule-shutdown | boolean | no | Try to abort active 'vzshutdown' tasks before stopping. |
| skiplock | boolean | no | Ignore locks - only root is allowed to use this option. |

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
    "/vms/{vmid}",
    [
      "VM.PowerMgmt"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Stop the container. This will abruptly stop all processes running in the container.",
  "method": "POST",
  "name": "vm_stop",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "overrule-shutdown": {
        "default": 0,
        "description": "Try to abort active 'vzshutdown' tasks before stopping.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "skiplock": {
        "description": "Ignore locks - only root is allowed to use this option.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.PowerMgmt"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
