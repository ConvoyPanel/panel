# POST /cluster/replication

Create a new replication job

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'. |
| target | string | yes | Target node. |
| type | string | yes | Section type. |
| comment | string | no | Description. |
| disable | boolean | no | Flag to disable/deactivate the entry. |
| rate | number | no | Rate limit in mbps (megabytes per second) as floating point number. |
| remove_job | string | no | Mark the replication job for removal. The job will remove all local replication snapshots. When set to 'full', it also tries to remove replicated volumes on the target. The job then removes itself from the configuration file. |
| schedule | string | no | Storage replication schedule. The format is a subset of `systemd` calendar events. |
| source | string | no | For internal use, to detect if the guest was stolen. |

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
  "description": "Create a new replication job",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "description": "Description.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "disable": {
        "description": "Flag to disable/deactivate the entry.",
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
      "rate": {
        "description": "Rate limit in mbps (megabytes per second) as floating point number.",
        "minimum": 1,
        "optional": 1,
        "type": "number",
        "typetext": "<number> (1 - N)"
      },
      "remove_job": {
        "description": "Mark the replication job for removal. The job will remove all local replication snapshots. When set to 'full', it also tries to remove replicated volumes on the target. The job then removes itself from the configuration file.",
        "enum": [
          "local",
          "full"
        ],
        "optional": 1,
        "type": "string"
      },
      "schedule": {
        "default": "*/15",
        "description": "Storage replication schedule. The format is a subset of `systemd` calendar events.",
        "format": "pve-calendar-event",
        "maxLength": 128,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "source": {
        "description": "For internal use, to detect if the guest was stolen.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "target": {
        "description": "Target node.",
        "format": "pve-node",
        "optional": 0,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "Section type.",
        "enum": [
          "local"
        ],
        "type": "string"
      }
    },
    "type": "object"
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
