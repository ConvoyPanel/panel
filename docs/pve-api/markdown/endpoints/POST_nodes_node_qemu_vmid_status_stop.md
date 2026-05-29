# POST /nodes/{node}/qemu/{vmid}/status/stop

Stop virtual machine. The qemu process will exit immediately. This is akin to pulling the power plug of a running computer and may damage the VM data.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| keepActive | boolean | no | Do not deactivate storage volumes. |
| migratedfrom | string | no | The cluster node name. |
| overrule-shutdown | boolean | no | Try to abort active 'qmshutdown' tasks before stopping. |
| skiplock | boolean | no | Ignore locks - only root is allowed to use this option. |
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
  "description": "Stop virtual machine. The qemu process will exit immediately. This is akin to pulling the power plug of a running computer and may damage the VM data.",
  "method": "POST",
  "name": "vm_stop",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "keepActive": {
        "default": 0,
        "description": "Do not deactivate storage volumes.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "migratedfrom": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "overrule-shutdown": {
        "default": 0,
        "description": "Try to abort active 'qmshutdown' tasks before stopping.",
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
      "timeout": {
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
