# PUT /nodes/{node}/qemu/{vmid}/unlink

Unlink/delete disk images.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| idlist | string | yes | A list of disk IDs you want to delete. |
| force | boolean | no | Force physical removal. Without this, we simple remove the disk from the config file and create an additional configuration entry called 'unused[n]', which contains the volume ID. Unlink of unused[n] always cause physical removal. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Config.Disk"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Unlink/delete disk images.",
  "method": "PUT",
  "name": "unlink",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force": {
        "description": "Force physical removal. Without this, we simple remove the disk from the config file and create an additional configuration entry called 'unused[n]', which contains the volume ID. Unlink of unused[n] always cause physical removal.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "idlist": {
        "description": "A list of disk IDs you want to delete.",
        "format": "pve-configid-list",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
        "VM.Config.Disk"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
