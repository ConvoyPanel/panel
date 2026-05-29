# GET /cluster/jobs

Index for jobs related endpoints.

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

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Index for jobs related endpoints.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
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
