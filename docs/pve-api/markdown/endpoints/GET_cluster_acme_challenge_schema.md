# GET /cluster/acme/challenge-schema

Get schema of ACME challenge types.

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
      "id": {
        "type": "string"
      },
      "name": {
        "description": "Human readable name, falls back to id",
        "type": "string"
      },
      "schema": {
        "type": "object"
      },
      "type": {
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
  "description": "Get schema of ACME challenge types.",
  "method": "GET",
  "name": "challengeschema",
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
        "id": {
          "type": "string"
        },
        "name": {
          "description": "Human readable name, falls back to id",
          "type": "string"
        },
        "schema": {
          "type": "object"
        },
        "type": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
