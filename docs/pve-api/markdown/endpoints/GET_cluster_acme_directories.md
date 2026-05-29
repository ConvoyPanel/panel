# GET /cluster/acme/directories

Get named known ACME directory endpoints.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "type": "string"
      },
      "url": {
        "description": "URL of ACME CA directory endpoint.",
        "pattern": "^https?://.*",
        "type": "string"
      }
    },
    "type": "object"
  },
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
  "description": "Get named known ACME directory endpoints.",
  "method": "GET",
  "name": "get_directories",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "additionalProperties": 0,
      "properties": {
        "name": {
          "type": "string"
        },
        "url": {
          "description": "URL of ACME CA directory endpoint.",
          "pattern": "^https?://.*",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
