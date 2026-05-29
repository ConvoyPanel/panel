# DELETE /nodes/{node}/qemu/{vmid}/firewall/aliases/{name}

Remove IP or Network alias.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Alias name. |
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |

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
      "VM.Config.Network"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Remove IP or Network alias.",
  "method": "DELETE",
  "name": "remove_alias",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "name": {
        "description": "Alias name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
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
        "VM.Config.Network"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
