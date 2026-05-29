# GET /nodes/{node}/sdn/fabrics/{fabric}/neighbors

Get all neighbors for a fabric.

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
      "neighbor": {
        "description": "The IP or hostname of the neighbor.",
        "type": "string"
      },
      "status": {
        "description": "The status of the neighbor, as returned by FRR.",
        "type": "string"
      },
      "uptime": {
        "description": "The uptime of this neighbor, as returned by FRR (e.g. 8h24m12s).",
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
  "description": "Get all neighbors for a fabric.",
  "method": "GET",
  "name": "neighbors",
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
        "neighbor": {
          "description": "The IP or hostname of the neighbor.",
          "type": "string"
        },
        "status": {
          "description": "The status of the neighbor, as returned by FRR.",
          "type": "string"
        },
        "uptime": {
          "description": "The uptime of this neighbor, as returned by FRR (e.g. 8h24m12s).",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
