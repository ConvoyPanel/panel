# GET /cluster/replication/{id}

Read replication job configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "comment": {
      "description": "Description.",
      "maxLength": 4096,
      "optional": 1,
      "type": "string"
    },
    "digest": {
      "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
      "maxLength": 64,
      "optional": 1,
      "type": "string"
    },
    "disable": {
      "description": "Flag to disable/deactivate the entry.",
      "optional": 1,
      "type": "boolean"
    },
    "guest": {
      "description": "Guest ID.",
      "type": "integer"
    },
    "id": {
      "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
      "format": "pve-replication-job-id",
      "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
      "type": "string"
    },
    "jobnum": {
      "description": "Unique, sequential ID assigned to each job.",
      "type": "integer"
    },
    "rate": {
      "description": "Rate limit in mbps (megabytes per second) as floating point number.",
      "minimum": 1,
      "optional": 1,
      "type": "number"
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
      "type": "string"
    },
    "source": {
      "description": "For internal use, to detect if the guest was stolen.",
      "format": "pve-node",
      "optional": 1,
      "type": "string"
    },
    "target": {
      "description": "Target node.",
      "format": "pve-node",
      "optional": 0,
      "type": "string"
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
  "description": "Read replication job configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
        "format": "pve-replication-job-id",
        "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Requires the VM.Audit permission on /vms/<vmid>.",
    "user": "all"
  },
  "returns": {
    "properties": {
      "comment": {
        "description": "Description.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string"
      },
      "disable": {
        "description": "Flag to disable/deactivate the entry.",
        "optional": 1,
        "type": "boolean"
      },
      "guest": {
        "description": "Guest ID.",
        "type": "integer"
      },
      "id": {
        "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
        "format": "pve-replication-job-id",
        "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
        "type": "string"
      },
      "jobnum": {
        "description": "Unique, sequential ID assigned to each job.",
        "type": "integer"
      },
      "rate": {
        "description": "Rate limit in mbps (megabytes per second) as floating point number.",
        "minimum": 1,
        "optional": 1,
        "type": "number"
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
        "type": "string"
      },
      "source": {
        "description": "For internal use, to detect if the guest was stolen.",
        "format": "pve-node",
        "optional": 1,
        "type": "string"
      },
      "target": {
        "description": "Target node.",
        "format": "pve-node",
        "optional": 0,
        "type": "string"
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
  }
}
```
