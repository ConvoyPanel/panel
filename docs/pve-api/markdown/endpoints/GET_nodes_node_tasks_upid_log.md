# GET /nodes/{node}/tasks/{upid}/log

Read task log.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| upid | string | yes | The task's unique ID. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| download | boolean | no | Whether the tasklog file should be downloaded. This parameter can't be used in conjunction with other parameters |
| limit | integer | no | The number of lines to read from the tasklog. |
| start | integer | no | Start at this line when reading the tasklog |

## Returns

```json
{
  "items": {
    "properties": {
      "n": {
        "description": "Line number",
        "type": "integer"
      },
      "t": {
        "description": "Line text",
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
  "description": "The user needs 'Sys.Audit' permissions on '/nodes/<node>' if they aren't the owner of the task.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read task log.",
  "download_allowed": 1,
  "method": "GET",
  "name": "read_task_log",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "download": {
        "description": "Whether the tasklog file should be downloaded. This parameter can't be used in conjunction with other parameters",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "limit": {
        "default": 50,
        "description": "The number of lines to read from the tasklog.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "start": {
        "default": 0,
        "description": "Start at this line when reading the tasklog",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "upid": {
        "description": "The task's unique ID.",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'Sys.Audit' permissions on '/nodes/<node>' if they aren't the owner of the task.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "n": {
          "description": "Line number",
          "type": "integer"
        },
        "t": {
          "description": "Line text",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
