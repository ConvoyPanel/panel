# POST /nodes/{node}/qemu/{vmid}/status/suspend

Suspend virtual machine.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| skiplock | boolean | no | Ignore locks - only root is allowed to use this option. |
| statestorage | string | no | The storage for the VM state |
| todisk | boolean | no | If set, suspends the VM to disk. Will be resumed on next VM start. |

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
  ],
  "description": "You need 'VM.PowerMgmt' on /vms/{vmid}, and if you have set 'todisk', you need also 'VM.Config.Disk' on /vms/{vmid} and 'Datastore.AllocateSpace' on the storage for the vmstate."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Suspend virtual machine.",
  "method": "POST",
  "name": "vm_suspend",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
      "statestorage": {
        "description": "The storage for the VM state",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "requires": "todisk",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "todisk": {
        "default": 0,
        "description": "If set, suspends the VM to disk. Will be resumed on next VM start.",
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
    ],
    "description": "You need 'VM.PowerMgmt' on /vms/{vmid}, and if you have set 'todisk', you need also 'VM.Config.Disk' on /vms/{vmid} and 'Datastore.AllocateSpace' on the storage for the vmstate."
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
