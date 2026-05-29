# GET /cluster/ha/groups

Get HA groups. (deprecated in favor of HA rules)

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "group": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{group}",
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
  "description": "Get HA groups. (deprecated in favor of HA rules)",
  "method": "GET",
  "name": "index",
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
        "group": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{group}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
