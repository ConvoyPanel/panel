# GET /cluster/backup-info

Index for backup info related endpoints

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "description": "Directory index.",
  "items": {
    "properties": {
      "subdir": {
        "description": "API sub-directory endpoint",
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

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Index for backup info related endpoints",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "returns": {
    "description": "Directory index.",
    "items": {
      "properties": {
        "subdir": {
          "description": "API sub-directory endpoint",
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
