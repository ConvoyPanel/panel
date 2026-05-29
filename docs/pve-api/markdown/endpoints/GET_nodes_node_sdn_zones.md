# GET /nodes/{node}/sdn/zones

Get status for all zones.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "status": {
        "description": "Status of zone",
        "enum": [
          "available",
          "pending",
          "error"
        ],
        "type": "string"
      },
      "zone": {
        "description": "The SDN zone object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{zone}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get status for all zones.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'SDN.Audit'",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "status": {
          "description": "Status of zone",
          "enum": [
            "available",
            "pending",
            "error"
          ],
          "type": "string"
        },
        "zone": {
          "description": "The SDN zone object identifier.",
          "maxLength": 8,
          "minLength": 2,
          "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{zone}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
