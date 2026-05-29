# GET /nodes/{node}/tasks

Read task list for one node (finished tasks).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| errors | boolean | no | Only list tasks with a status of ERROR. |
| limit | integer | no | Only list this number of tasks. |
| since | integer | no | Only list tasks since this UNIX epoch. |
| source | string | no | List archived, active or all tasks. |
| start | integer | no | List tasks beginning from this offset. |
| statusfilter | string | no | List of Task States that should be returned. |
| typefilter | string | no | Only list tasks of this type (e.g., vzstart, vzdump). |
| until | integer | no | Only list tasks until this UNIX epoch. |
| userfilter | string | no | Only list tasks from this user. |
| vmid | integer | no | Only list tasks for this VM. |

## Returns

```json
{
  "items": {
    "properties": {
      "endtime": {
        "optional": 1,
        "renderer": "timestamp",
        "title": "Endtime",
        "type": "integer"
      },
      "id": {
        "title": "ID",
        "type": "string"
      },
      "node": {
        "title": "Node",
        "type": "string"
      },
      "pid": {
        "title": "PID",
        "type": "integer"
      },
      "pstart": {
        "type": "integer"
      },
      "starttime": {
        "renderer": "timestamp",
        "title": "Starttime",
        "type": "integer"
      },
      "status": {
        "optional": 1,
        "title": "Status",
        "type": "string"
      },
      "type": {
        "title": "Type",
        "type": "string"
      },
      "upid": {
        "title": "UPID",
        "type": "string"
      },
      "user": {
        "title": "User",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{upid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "List task associated with the current user, or all task the user has 'Sys.Audit' permissions on /nodes/<node> (the <node> the task runs on).",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read task list for one node (finished tasks).",
  "method": "GET",
  "name": "node_tasks",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "errors": {
        "default": 0,
        "description": "Only list tasks with a status of ERROR.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "limit": {
        "default": 50,
        "description": "Only list this number of tasks.",
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
      "since": {
        "description": "Only list tasks since this UNIX epoch.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "source": {
        "default": "archive",
        "description": "List archived, active or all tasks.",
        "enum": [
          "archive",
          "active",
          "all"
        ],
        "optional": 1,
        "type": "string"
      },
      "start": {
        "default": 0,
        "description": "List tasks beginning from this offset.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "statusfilter": {
        "description": "List of Task States that should be returned.",
        "format": "pve-task-status-type-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "typefilter": {
        "description": "Only list tasks of this type (e.g., vzstart, vzdump).",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "until": {
        "description": "Only list tasks until this UNIX epoch.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "userfilter": {
        "description": "Only list tasks from this user.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "Only list tasks for this VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "description": "List task associated with the current user, or all task the user has 'Sys.Audit' permissions on /nodes/<node> (the <node> the task runs on).",
    "user": "all"
  },
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "endtime": {
          "optional": 1,
          "renderer": "timestamp",
          "title": "Endtime",
          "type": "integer"
        },
        "id": {
          "title": "ID",
          "type": "string"
        },
        "node": {
          "title": "Node",
          "type": "string"
        },
        "pid": {
          "title": "PID",
          "type": "integer"
        },
        "pstart": {
          "type": "integer"
        },
        "starttime": {
          "renderer": "timestamp",
          "title": "Starttime",
          "type": "integer"
        },
        "status": {
          "optional": 1,
          "title": "Status",
          "type": "string"
        },
        "type": {
          "title": "Type",
          "type": "string"
        },
        "upid": {
          "title": "UPID",
          "type": "string"
        },
        "user": {
          "title": "User",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{upid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
