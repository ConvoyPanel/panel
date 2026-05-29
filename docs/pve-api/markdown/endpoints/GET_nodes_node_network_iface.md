# GET /nodes/{node}/network/{iface}

Read network device configuration

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| iface | string | yes | Network interface name. |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "method": {
      "type": "string"
    },
    "type": {
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
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read network device configuration",
  "method": "GET",
  "name": "network_config",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "iface": {
        "description": "Network interface name.",
        "format": "pve-iface",
        "maxLength": 20,
        "minLength": 2,
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
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "properties": {
      "method": {
        "type": "string"
      },
      "type": {
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
