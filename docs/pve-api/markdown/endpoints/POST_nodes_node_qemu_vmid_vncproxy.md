# POST /nodes/{node}/qemu/{vmid}/vncproxy

Creates a TCP VNC proxy connections.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| generate-password | boolean | no | Deprecated, do not use. Password is generated when required. |
| websocket | boolean | no | Prepare for websocket upgrade (only required when using serial terminal, otherwise upgrade is always possible). |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "cert": {
      "type": "string"
    },
    "password": {
      "description": "Password used for authentication within the VNC protocol. Consists of printable ASCII characters ('!' .. '~').",
      "optional": 1,
      "type": "string"
    },
    "port": {
      "type": "integer"
    },
    "ticket": {
      "type": "string"
    },
    "upid": {
      "type": "string"
    },
    "user": {
      "type": "string"
    }
  }
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Creates a TCP VNC proxy connections.",
  "method": "POST",
  "name": "vncproxy",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "generate-password": {
        "default": 0,
        "description": "Deprecated, do not use. Password is generated when required.",
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
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "websocket": {
        "description": "Prepare for websocket upgrade (only required when using serial terminal, otherwise upgrade is always possible).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Console"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "cert": {
        "type": "string"
      },
      "password": {
        "description": "Password used for authentication within the VNC protocol. Consists of printable ASCII characters ('!' .. '~').",
        "optional": 1,
        "type": "string"
      },
      "port": {
        "type": "integer"
      },
      "ticket": {
        "type": "string"
      },
      "upid": {
        "type": "string"
      },
      "user": {
        "type": "string"
      }
    }
  }
}
```
