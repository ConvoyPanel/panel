# DELETE /cluster/replication/{id}

Mark replication job for removal.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force | boolean | no | Will remove the jobconfig entry, but will not cleanup. |
| keep | boolean | no | Keep replicated data at target (do not remove). |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "description": "Requires the VM.Replicate permission on /vms/<vmid>.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Mark replication job for removal.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force": {
        "default": 0,
        "description": "Will remove the jobconfig entry, but will not cleanup.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "id": {
        "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
        "format": "pve-replication-job-id",
        "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
        "type": "string"
      },
      "keep": {
        "default": 0,
        "description": "Keep replicated data at target (do not remove).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "description": "Requires the VM.Replicate permission on /vms/<vmid>.",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
