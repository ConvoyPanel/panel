# POST /nodes/{node}/qemu/{vmid}/agent/set-user-password

Sets the password for the given user to the given password

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| password | string | yes | The new password. |
| username | string | yes | The user to set the password for. |
| crypted | boolean | no | set to 1 if the password has already been passed through crypt() |

## Returns

```json
{
  "description": "Returns an object with a single `result` property.",
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.GuestAgent.Unrestricted"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Sets the password for the given user to the given password",
  "method": "POST",
  "name": "set-user-password",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "crypted": {
        "default": 0,
        "description": "set to 1 if the password has already been passed through crypt()",
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
      "password": {
        "description": "The new password.",
        "maxLength": 1024,
        "minLength": 5,
        "type": "string",
        "typetext": "<string>"
      },
      "username": {
        "description": "The user to set the password for.",
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
        "VM.GuestAgent.Unrestricted"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "Returns an object with a single `result` property.",
    "type": "object"
  }
}
```
