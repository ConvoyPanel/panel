# GET /nodes/{node}/replication/{id}/log

Read replication job log.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| limit | integer | no |  |
| start | integer | no |  |

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
  "description": "Requires the VM.Audit permission on /vms/<vmid>, or 'Sys.Audit' on '/nodes/<node>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read replication job log.",
  "method": "GET",
  "name": "read_job_log",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
        "format": "pve-replication-job-id",
        "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
        "type": "string"
      },
      "limit": {
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
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      }
    }
  },
  "permissions": {
    "description": "Requires the VM.Audit permission on /vms/<vmid>, or 'Sys.Audit' on '/nodes/<node>'",
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
