# GET /nodes/{node}/tasks/{upid}/status

Read task status.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| upid | string | yes | The task's unique ID. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "exitstatus": {
      "optional": 1,
      "type": "string"
    },
    "id": {
      "type": "string"
    },
    "node": {
      "type": "string"
    },
    "pid": {
      "type": "integer"
    },
    "pstart": {
      "type": "integer"
    },
    "starttime": {
      "type": "integer"
    },
    "status": {
      "enum": [
        "running",
        "stopped"
      ],
      "type": "string"
    },
    "type": {
      "type": "string"
    },
    "upid": {
      "type": "string"
    },
    "user": {
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "The user needs 'Sys.Audit' permissions on '/nodes/<node>' if they are not the owner of the task.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read task status.",
  "method": "GET",
  "name": "read_task_status",
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
        "description": "The task's unique ID.",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'Sys.Audit' permissions on '/nodes/<node>' if they are not the owner of the task.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "exitstatus": {
        "optional": 1,
        "type": "string"
      },
      "id": {
        "type": "string"
      },
      "node": {
        "type": "string"
      },
      "pid": {
        "type": "integer"
      },
      "pstart": {
        "type": "integer"
      },
      "starttime": {
        "type": "integer"
      },
      "status": {
        "enum": [
          "running",
          "stopped"
        ],
        "type": "string"
      },
      "type": {
        "type": "string"
      },
      "upid": {
        "type": "string"
      },
      "user": {
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
