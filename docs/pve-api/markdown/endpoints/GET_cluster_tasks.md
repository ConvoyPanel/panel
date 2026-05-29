# GET /cluster/tasks

List recent tasks (cluster wide).

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "upid": {
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
  "description": "List recent tasks (cluster wide).",
  "method": "GET",
  "name": "tasks",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "upid": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
