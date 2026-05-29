# GET /cluster/config/nodes

Corosync node list.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "node": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{node}",
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
  "description": "Corosync node list.",
  "method": "GET",
  "name": "nodes",
  "parameters": {
    "additionalProperties": 0
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
  "returns": {
    "items": {
      "properties": {
        "node": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{node}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
