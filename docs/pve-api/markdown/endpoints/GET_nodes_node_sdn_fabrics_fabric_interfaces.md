# GET /nodes/{node}/sdn/fabrics/{fabric}/interfaces

Get all interfaces for a fabric.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| fabric | string | yes | Identifier for SDN fabrics |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "name": {
        "description": "The name of the network interface.",
        "type": "string"
      },
      "state": {
        "description": "The current state of the interface.",
        "type": "string"
      },
      "type": {
        "description": "The type of this interface in the fabric (e.g. Point-to-Point, Broadcast, ..).",
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/fabrics/{fabric}",
    [
      "SDN.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get all interfaces for a fabric.",
  "method": "GET",
  "name": "interfaces",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "fabric": {
        "description": "Identifier for SDN fabrics",
        "format": "pve-sdn-fabric-id",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z0-9][a-zA-Z0-9-]{0,6}[a-zA-Z0-9]",
        "type": "string"
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
      "/sdn/fabrics/{fabric}",
      [
        "SDN.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "name": {
          "description": "The name of the network interface.",
          "type": "string"
        },
        "state": {
          "description": "The current state of the interface.",
          "type": "string"
        },
        "type": {
          "description": "The type of this interface in the fabric (e.g. Point-to-Point, Broadcast, ..).",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
