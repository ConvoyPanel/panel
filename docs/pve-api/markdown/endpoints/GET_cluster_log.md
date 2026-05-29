# GET /cluster/log

Read cluster log

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| max | integer | no | Maximum number of entries. |

## Returns

```json
{
  "items": {
    "properties": {},
    "type": "object"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "description": "The user needs 'Sys.Syslog' on '/' in order to get all logs.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read cluster log",
  "method": "GET",
  "name": "log",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "max": {
        "description": "Maximum number of entries.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'Sys.Syslog' on '/' in order to get all logs.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "type": "array"
  }
}
```
