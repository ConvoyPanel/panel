# GET /cluster/notifications

Index for notification-related API endpoints.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {},
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Index for notification-related API endpoints.",
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
      "properties": {},
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
