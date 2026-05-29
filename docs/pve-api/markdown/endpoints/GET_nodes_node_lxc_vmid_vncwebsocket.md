# GET /nodes/{node}/lxc/{vmid}/vncwebsocket

Opens a websocket for VNC traffic.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| port | integer | yes | Port number returned by previous vncproxy call. |
| vncticket | string | yes | Ticket from previous call to vncproxy. |

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
    "/vms/{vmid}",
    [
      "VM.Console"
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
        "description": "Port number returned by previous vncproxy call.",
        "maximum": 5999,
        "minimum": 5900,
        "type": "integer",
        "typetext": "<integer> (5900 - 5999)"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "vncticket": {
        "description": "Ticket from previous call to vncproxy.",
        "maxLength": 512,
        "type": "string",
        "typetext": "<string>"
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
