# GET /nodes/{node}/lxc/{vmid}/mtunnelwebsocket

Migration tunnel endpoint for websocket upgrade - only for internal use by VM migration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| socket | string | yes | unix socket to forward to |
| ticket | string | yes | ticket return by initial 'mtunnel' API call, or retrieved via 'ticket' tunnel command |

## Returns

```json
{
  "properties": {
    "port": {
      "optional": 1,
      "type": "string"
    },
    "socket": {
      "optional": 1,
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "You need to pass a ticket valid for the selected socket. Tickets can be created via the mtunnel API call, which will check permissions accordingly.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Migration tunnel endpoint for websocket upgrade - only for internal use by VM migration.",
  "method": "GET",
  "name": "mtunnelwebsocket",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "socket": {
        "description": "unix socket to forward to",
        "type": "string",
        "typetext": "<string>"
      },
      "ticket": {
        "description": "ticket return by initial 'mtunnel' API call, or retrieved via 'ticket' tunnel command",
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
    "description": "You need to pass a ticket valid for the selected socket. Tickets can be created via the mtunnel API call, which will check permissions accordingly.",
    "user": "all"
  },
  "returns": {
    "properties": {
      "port": {
        "optional": 1,
        "type": "string"
      },
      "socket": {
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
