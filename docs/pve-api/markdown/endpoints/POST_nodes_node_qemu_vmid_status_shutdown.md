# POST /nodes/{node}/qemu/{vmid}/status/shutdown

Shutdown virtual machine. This is similar to pressing the power button on a physical machine. This will send an ACPI event for the guest OS, which should then proceed to a clean shutdown.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| forceStop | boolean | no | Make sure the VM stops. |
| keepActive | boolean | no | Do not deactivate storage volumes. |
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
  "description": "Shutdown virtual machine. This is similar to pressing the power button on a physical machine. This will send an ACPI event for the guest OS, which should then proceed to a clean shutdown.",
  "method": "POST",
  "name": "vm_shutdown",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "forceStop": {
        "default": 0,
        "description": "Make sure the VM stops.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "keepActive": {
        "default": 0,
        "description": "Do not deactivate storage volumes.",
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
