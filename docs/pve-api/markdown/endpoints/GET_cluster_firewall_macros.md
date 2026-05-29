# GET /cluster/firewall/macros

List available macros

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "descr": {
        "description": "More verbose description (if available).",
        "type": "string"
      },
      "macro": {
        "description": "Macro name.",
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
  "description": "List available macros",
  "method": "GET",
  "name": "get_macros",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "descr": {
          "description": "More verbose description (if available).",
          "type": "string"
        },
        "macro": {
          "description": "Macro name.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
