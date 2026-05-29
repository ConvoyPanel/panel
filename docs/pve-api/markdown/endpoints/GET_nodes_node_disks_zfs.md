# GET /nodes/{node}/disks/zfs

List Zpools.

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
      "alloc": {
        "description": "",
        "type": "integer"
      },
      "dedup": {
        "description": "",
        "type": "number"
      },
      "frag": {
        "description": "",
        "type": "integer"
      },
      "free": {
        "description": "",
        "type": "integer"
      },
      "health": {
        "description": "",
        "type": "string"
      },
      "name": {
        "description": "",
        "type": "string"
      },
      "size": {
        "description": "",
        "type": "integer"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{name}",
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
    "/",
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
  "description": "List Zpools.",
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
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "alloc": {
          "description": "",
          "type": "integer"
        },
        "dedup": {
          "description": "",
          "type": "number"
        },
        "frag": {
          "description": "",
          "type": "integer"
        },
        "free": {
          "description": "",
          "type": "integer"
        },
        "health": {
          "description": "",
          "type": "string"
        },
        "name": {
          "description": "",
          "type": "string"
        },
        "size": {
          "description": "",
          "type": "integer"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
