# POST /nodes/{node}/termproxy

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

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "port": {
      "description": "port used to bind termproxy to.",
      "type": "integer"
    },
    "ticket": {
      "description": "VNC ticket used to verify websocket connection.",
      "type": "string"
    },
    "upid": {
      "description": "UPID for termproxy worker task.",
      "type": "string"
    },
    "user": {
      "description": "user/token that generated the VNC ticket in `ticket`.",
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
  "name": "termproxy",
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
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
      "port": {
        "description": "port used to bind termproxy to.",
        "type": "integer"
      },
      "ticket": {
        "description": "VNC ticket used to verify websocket connection.",
        "type": "string"
      },
      "upid": {
        "description": "UPID for termproxy worker task.",
        "type": "string"
      },
      "user": {
        "description": "user/token that generated the VNC ticket in `ticket`.",
        "type": "string"
      }
    }
  }
}
```
