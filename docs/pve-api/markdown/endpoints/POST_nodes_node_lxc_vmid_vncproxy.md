# POST /nodes/{node}/lxc/{vmid}/vncproxy

Creates a TCP VNC proxy connections.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| height | integer | no | sets the height of the console in pixels. |
| websocket | boolean | no | use websocket instead of standard VNC. |
| width | integer | no | sets the width of the console in pixels. |

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
      "height": {
        "description": "sets the height of the console in pixels.",
        "maximum": 2160,
        "minimum": 16,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (16 - 2160)"
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
        "description": "use websocket instead of standard VNC.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "width": {
        "description": "sets the width of the console in pixels.",
        "maximum": 4096,
        "minimum": 16,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (16 - 4096)"
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
