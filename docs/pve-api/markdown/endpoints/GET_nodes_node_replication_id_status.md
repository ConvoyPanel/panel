# GET /nodes/{node}/replication/{id}/status

Get replication job status.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'. |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "type": "object"
}
```

## Permissions

```json
{
  "description": "Requires the VM.Audit permission on /vms/<vmid>.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get replication job status.",
  "method": "GET",
  "name": "job_status",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
        "format": "pve-replication-job-id",
        "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Requires the VM.Audit permission on /vms/<vmid>.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "object"
  }
}
```
