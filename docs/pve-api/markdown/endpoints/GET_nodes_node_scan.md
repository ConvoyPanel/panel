# GET /nodes/{node}/scan

Index of available scan methods

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
      "method": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{method}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Index of available scan methods",
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
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "method": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{method}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
