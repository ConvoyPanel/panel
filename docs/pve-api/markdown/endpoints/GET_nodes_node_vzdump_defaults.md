# GET /nodes/{node}/vzdump/defaults

Get the currently configured vzdump defaults.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| storage | string | no | The storage identifier. |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "all": {
      "default": 0,
      "description": "Backup all known guest systems on this host.",
      "optional": 1,
      "type": "boolean"
    },
    "bwlimit": {
      "default": 0,
      "description": "Limit I/O bandwidth (in KiB/s).",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "compress": {
      "default": "0",
      "description": "Compress dump file.",
      "enum": [
        "0",
        "1",
        "gzip",
        "lzo",
        "zstd"
      ],
      "optional": 1,
      "type": "string"
    },
    "dumpdir": {
      "description": "Store resulting files to specified directory.",
      "optional": 1,
      "type": "string"
    },
    "exclude": {
      "description": "Exclude specified guest systems (assumes --all)",
      "format": "pve-vmid-list",
      "optional": 1,
      "type": "string"
    },
    "exclude-path": {
      "description": "Exclude certain files/directories (shell globs). Paths starting with '/' are anchored to the container's root, other paths match relative to each subdirectory.",
      "items": {
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "fleecing": {
      "description": "Options for backup fleecing (VM only).",
      "format": "backup-fleecing",
      "optional": 1,
      "type": "string"
    },
    "ionice": {
      "default": 7,
      "description": "Set IO priority when using the BFQ scheduler. For snapshot and suspend mode backups of VMs, this only affects the compressor. A value of 8 means the idle priority is used, otherwise the best-effort priority is used with the specified value.",
      "maximum": 8,
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "lockwait": {
      "default": 180,
      "description": "Maximal time to wait for the global lock (minutes).",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "mailnotification": {
      "default": "always",
      "description": "Deprecated: use notification targets/matchers instead. Specify when to send a notification mail",
      "enum": [
        "always",
        "failure"
      ],
      "optional": 1,
      "type": "string"
    },
    "mailto": {
      "description": "Deprecated: Use notification targets/matchers instead. Comma-separated list of email addresses or users that should receive email notifications.",
      "format": "email-or-username-list",
      "optional": 1,
      "type": "string"
    },
    "mode": {
      "default": "snapshot",
      "description": "Backup mode.",
      "enum": [
        "snapshot",
        "suspend",
        "stop"
      ],
      "optional": 1,
      "type": "string"
    },
    "node": {
      "description": "Only run if executed on this node.",
      "format": "pve-node",
      "optional": 1,
      "type": "string"
    },
    "notes-template": {
      "description": "Template string for generating notes for the backup(s). It can contain variables which will be replaced by their values. Currently supported are {{cluster}}, {{guestname}}, {{node}}, and {{vmid}}, but more might be added in the future. Needs to be a single line, newline and backslash need to be escaped as '\\n' and '\\\\' respectively.",
      "maxLength": 1024,
      "optional": 1,
      "requires": "storage",
      "type": "string"
    },
    "notification-mode": {
      "default": "auto",
      "description": "Determine which notification system to use. If set to 'legacy-sendmail', vzdump will consider the mailto/mailnotification parameters and send emails to the specified address(es) via the 'sendmail' command. If set to 'notification-system', a notification will be sent via PVE's notification system, and the mailto and mailnotification will be ignored. If set to 'auto' (default setting), an email will be sent if mailto is set, and the notification system will be used if not.",
      "enum": [
        "auto",
        "legacy-sendmail",
        "notification-system"
      ],
      "optional": 1,
      "type": "string"
    },
    "pbs-change-detection-mode": {
      "description": "PBS mode used to detect file changes and switch encoding format for container backups.",
      "enum": [
        "legacy",
        "data",
        "metadata"
      ],
      "optional": 1,
      "type": "string"
    },
    "performance": {
      "description": "Other performance-related settings.",
      "format": "backup-performance",
      "optional": 1,
      "type": "string"
    },
    "pigz": {
      "default": 0,
      "description": "Use pigz instead of gzip when N>0. N=1 uses half of cores, N>1 uses N as thread count.",
      "optional": 1,
      "type": "integer"
    },
    "pool": {
      "description": "Backup all known guest systems included in the specified pool.",
      "optional": 1,
      "type": "string"
    },
    "protected": {
      "description": "If true, mark backup(s) as protected.",
      "optional": 1,
      "requires": "storage",
      "type": "boolean"
    },
    "prune-backups": {
      "default": "keep-all=1",
      "description": "Use these retention options instead of those from the storage configuration.",
      "format": "prune-backups",
      "optional": 1,
      "type": "string"
    },
    "quiet": {
      "default": 0,
      "description": "Be quiet.",
      "optional": 1,
      "type": "boolean"
    },
    "remove": {
      "default": 1,
      "description": "Prune older backups according to 'prune-backups'.",
      "optional": 1,
      "type": "boolean"
    },
    "script": {
      "description": "Use specified hook script.",
      "optional": 1,
      "type": "string"
    },
    "stdexcludes": {
      "default": 1,
      "description": "Exclude temporary files and logs.",
      "optional": 1,
      "type": "boolean"
    },
    "stop": {
      "default": 0,
      "description": "Stop running backup jobs on this host.",
      "optional": 1,
      "type": "boolean"
    },
    "stopwait": {
      "default": 10,
      "description": "Maximal time to wait until a guest system is stopped (minutes).",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "storage": {
      "description": "Store resulting file to this storage.",
      "format": "pve-storage-id",
      "format_description": "storage ID",
      "optional": 1,
      "type": "string"
    },
    "tmpdir": {
      "description": "Store temporary files to specified directory.",
      "optional": 1,
      "type": "string"
    },
    "vmid": {
      "description": "The ID of the guest system you want to backup.",
      "format": "pve-vmid-list",
      "optional": 1,
      "type": "string"
    },
    "zstd": {
      "default": 1,
      "description": "Zstd threads. N=0 uses half of the available cores, if N is set to a value bigger than 0, N is used as thread count.",
      "optional": 1,
      "type": "integer"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "The user needs 'Datastore.Audit' or 'Datastore.AllocateSpace' permissions for the specified storage (or default storage if none specified). Some properties are only returned when the user has 'Sys.Audit' permissions for the node.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get the currently configured vzdump defaults.",
  "method": "GET",
  "name": "defaults",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string",
        "typetext": "<storage ID>"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'Datastore.Audit' or 'Datastore.AllocateSpace' permissions for the specified storage (or default storage if none specified). Some properties are only returned when the user has 'Sys.Audit' permissions for the node.",
    "user": "all"
  },
  "proxyto": "node",
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "all": {
        "default": 0,
        "description": "Backup all known guest systems on this host.",
        "optional": 1,
        "type": "boolean"
      },
      "bwlimit": {
        "default": 0,
        "description": "Limit I/O bandwidth (in KiB/s).",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "compress": {
        "default": "0",
        "description": "Compress dump file.",
        "enum": [
          "0",
          "1",
          "gzip",
          "lzo",
          "zstd"
        ],
        "optional": 1,
        "type": "string"
      },
      "dumpdir": {
        "description": "Store resulting files to specified directory.",
        "optional": 1,
        "type": "string"
      },
      "exclude": {
        "description": "Exclude specified guest systems (assumes --all)",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string"
      },
      "exclude-path": {
        "description": "Exclude certain files/directories (shell globs). Paths starting with '/' are anchored to the container's root, other paths match relative to each subdirectory.",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "fleecing": {
        "description": "Options for backup fleecing (VM only).",
        "format": "backup-fleecing",
        "optional": 1,
        "type": "string"
      },
      "ionice": {
        "default": 7,
        "description": "Set IO priority when using the BFQ scheduler. For snapshot and suspend mode backups of VMs, this only affects the compressor. A value of 8 means the idle priority is used, otherwise the best-effort priority is used with the specified value.",
        "maximum": 8,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "lockwait": {
        "default": 180,
        "description": "Maximal time to wait for the global lock (minutes).",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "mailnotification": {
        "default": "always",
        "description": "Deprecated: use notification targets/matchers instead. Specify when to send a notification mail",
        "enum": [
          "always",
          "failure"
        ],
        "optional": 1,
        "type": "string"
      },
      "mailto": {
        "description": "Deprecated: Use notification targets/matchers instead. Comma-separated list of email addresses or users that should receive email notifications.",
        "format": "email-or-username-list",
        "optional": 1,
        "type": "string"
      },
      "mode": {
        "default": "snapshot",
        "description": "Backup mode.",
        "enum": [
          "snapshot",
          "suspend",
          "stop"
        ],
        "optional": 1,
        "type": "string"
      },
      "node": {
        "description": "Only run if executed on this node.",
        "format": "pve-node",
        "optional": 1,
        "type": "string"
      },
      "notes-template": {
        "description": "Template string for generating notes for the backup(s). It can contain variables which will be replaced by their values. Currently supported are {{cluster}}, {{guestname}}, {{node}}, and {{vmid}}, but more might be added in the future. Needs to be a single line, newline and backslash need to be escaped as '\\n' and '\\\\' respectively.",
        "maxLength": 1024,
        "optional": 1,
        "requires": "storage",
        "type": "string"
      },
      "notification-mode": {
        "default": "auto",
        "description": "Determine which notification system to use. If set to 'legacy-sendmail', vzdump will consider the mailto/mailnotification parameters and send emails to the specified address(es) via the 'sendmail' command. If set to 'notification-system', a notification will be sent via PVE's notification system, and the mailto and mailnotification will be ignored. If set to 'auto' (default setting), an email will be sent if mailto is set, and the notification system will be used if not.",
        "enum": [
          "auto",
          "legacy-sendmail",
          "notification-system"
        ],
        "optional": 1,
        "type": "string"
      },
      "pbs-change-detection-mode": {
        "description": "PBS mode used to detect file changes and switch encoding format for container backups.",
        "enum": [
          "legacy",
          "data",
          "metadata"
        ],
        "optional": 1,
        "type": "string"
      },
      "performance": {
        "description": "Other performance-related settings.",
        "format": "backup-performance",
        "optional": 1,
        "type": "string"
      },
      "pigz": {
        "default": 0,
        "description": "Use pigz instead of gzip when N>0. N=1 uses half of cores, N>1 uses N as thread count.",
        "optional": 1,
        "type": "integer"
      },
      "pool": {
        "description": "Backup all known guest systems included in the specified pool.",
        "optional": 1,
        "type": "string"
      },
      "protected": {
        "description": "If true, mark backup(s) as protected.",
        "optional": 1,
        "requires": "storage",
        "type": "boolean"
      },
      "prune-backups": {
        "default": "keep-all=1",
        "description": "Use these retention options instead of those from the storage configuration.",
        "format": "prune-backups",
        "optional": 1,
        "type": "string"
      },
      "quiet": {
        "default": 0,
        "description": "Be quiet.",
        "optional": 1,
        "type": "boolean"
      },
      "remove": {
        "default": 1,
        "description": "Prune older backups according to 'prune-backups'.",
        "optional": 1,
        "type": "boolean"
      },
      "script": {
        "description": "Use specified hook script.",
        "optional": 1,
        "type": "string"
      },
      "stdexcludes": {
        "default": 1,
        "description": "Exclude temporary files and logs.",
        "optional": 1,
        "type": "boolean"
      },
      "stop": {
        "default": 0,
        "description": "Stop running backup jobs on this host.",
        "optional": 1,
        "type": "boolean"
      },
      "stopwait": {
        "default": 10,
        "description": "Maximal time to wait until a guest system is stopped (minutes).",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "storage": {
        "description": "Store resulting file to this storage.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string"
      },
      "tmpdir": {
        "description": "Store temporary files to specified directory.",
        "optional": 1,
        "type": "string"
      },
      "vmid": {
        "description": "The ID of the guest system you want to backup.",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string"
      },
      "zstd": {
        "default": 1,
        "description": "Zstd threads. N=0 uses half of the available cores, if N is set to a value bigger than 0, N is used as thread count.",
        "optional": 1,
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
