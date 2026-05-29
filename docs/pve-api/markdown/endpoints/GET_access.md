# GET /access

Directory index.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "subdir": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{subdir}",
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
  "description": "Directory index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "subdir": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{subdir}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
