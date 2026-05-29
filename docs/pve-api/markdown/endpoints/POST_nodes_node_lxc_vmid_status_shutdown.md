# POST /nodes/{node}/lxc/{vmid}/status/shutdown

Shutdown the container. This will trigger a clean shutdown of the container, see lxc-stop(1) for details.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| forceStop | boolean | no | Make sure the Container stops. |
| timeout | integer | no | Wait maximal timeout seconds. |

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
  "description": "Shutdown the container. This will trigger a clean shutdown of the container, see lxc-stop(1) for details.",
  "method": "POST",
  "name": "vm_shutdown",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "forceStop": {
        "default": 0,
        "description": "Make sure the Container stops.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "timeout": {
        "default": 60,
        "description": "Wait maximal timeout seconds.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
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
