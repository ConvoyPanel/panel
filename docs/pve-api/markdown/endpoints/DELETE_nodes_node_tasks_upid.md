# DELETE /nodes/{node}/tasks/{upid}

Stop a task.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| upid | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "description": "The user needs 'Sys.Modify' permissions on '/nodes/<node>' if they aren't the owner of the task.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Stop a task.",
  "method": "DELETE",
  "name": "stop_task",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "upid": {
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'Sys.Modify' permissions on '/nodes/<node>' if they aren't the owner of the task.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
