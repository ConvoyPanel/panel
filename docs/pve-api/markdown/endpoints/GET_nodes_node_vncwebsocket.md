# GET /nodes/{node}/vncwebsocket

Opens a websocket for VNC traffic.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| port | integer | yes | Port number returned by previous 'vncshell' call. |
| vncticket | string | yes | Ticket from previous call to 'vncshell'. |

## Returns

```json
{
  "properties": {
    "port": {
      "type": "string"
    }
  },
  "type": "object"
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
  ],
  "description": "You also need to pass a valid ticket (vncticket)."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Opens a websocket for VNC traffic.",
  "method": "GET",
  "name": "vncwebsocket",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "description": "Port number returned by previous 'vncshell' call.",
        "maximum": 5999,
        "minimum": 5900,
        "type": "integer",
        "typetext": "<integer> (5900 - 5999)"
      },
      "vncticket": {
        "description": "Ticket from previous call to 'vncshell'.",
        "maxLength": 512,
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
    ],
    "description": "You also need to pass a valid ticket (vncticket)."
  },
  "returns": {
    "properties": {
      "port": {
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
