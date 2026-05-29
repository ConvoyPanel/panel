# GET /cluster/ha/resources

List HA resources.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list resources of specific type |

## Returns

```json
{
  "items": {
    "properties": {
      "sid": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{sid}",
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
  "description": "List HA resources.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Only list resources of specific type",
        "enum": [
          "ct",
          "vm"
        ],
        "optional": 1,
        "type": "string"
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
  "returns": {
    "items": {
      "properties": {
        "sid": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{sid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
