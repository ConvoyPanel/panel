# GET /nodes/{node}/sdn/zones/{zone}/content

List zone content.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| zone | string | yes | The SDN zone object identifier. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "status": {
        "description": "Status.",
        "optional": 1,
        "type": "string"
      },
      "statusmsg": {
        "description": "Status details",
        "optional": 1,
        "type": "string"
      },
      "vnet": {
        "description": "Vnet identifier.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{vnet}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/zones/{zone}",
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
  "description": "List zone content.",
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
      },
      "zone": {
        "description": "The SDN zone object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/zones/{zone}",
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
        "status": {
          "description": "Status.",
          "optional": 1,
          "type": "string"
        },
        "statusmsg": {
          "description": "Status details",
          "optional": 1,
          "type": "string"
        },
        "vnet": {
          "description": "Vnet identifier.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{vnet}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
