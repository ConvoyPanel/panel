# POST /nodes/{node}/vncshell

Creates a VNC Shell proxy.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cmd | string | no | Run specific command or default to login (requires 'root@pam') |
| cmd-opts | string | no | Add parameters to a command. Encoded as null terminated strings. |
| height | integer | no | sets the height of the console in pixels. |
| websocket | boolean | no | use websocket instead of standard vnc. |
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
    "/nodes/{node}",
    [
      "Sys.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Creates a VNC Shell proxy.",
  "method": "POST",
  "name": "vncshell",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cmd": {
        "default": "login",
        "description": "Run specific command or default to login (requires 'root@pam')",
        "enum": [
          "ceph_install",
          "login",
          "upgrade"
        ],
        "optional": 1,
        "type": "string"
      },
      "cmd-opts": {
        "default": "",
        "description": "Add parameters to a command. Encoded as null terminated strings.",
        "optional": 1,
        "requires": "cmd",
        "type": "string",
        "typetext": "<string>"
      },
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
      "websocket": {
        "description": "use websocket instead of standard vnc.",
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
      "/nodes/{node}",
      [
        "Sys.Console"
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
