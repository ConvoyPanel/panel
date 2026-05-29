# POST /nodes/{node}/vzdump

Create backup.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | no | Only run if executed on this node. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| all | boolean | no | Backup all known guest systems on this host. |
| bwlimit | integer | no | Limit I/O bandwidth (in KiB/s). |
| compress | string | no | Compress dump file. |
| dumpdir | string | no | Store resulting files to specified directory. |
| exclude | string | no | Exclude specified guest systems (assumes --all) |
| exclude-path | array | no | Exclude certain files/directories (shell globs). Paths starting with '/' are anchored to the container's root, other paths match relative to each subdirectory. |
| fleecing | string | no | Options for backup fleecing (VM only). |
| ionice | integer | no | Set IO priority when using the BFQ scheduler. For snapshot and suspend mode backups of VMs, this only affects the compressor. A value of 8 means the idle priority is used, otherwise the best-effort priority is used with the specified value. |
| job-id | string | no | The ID of the backup job. If set, the 'backup-job' metadata field of the backup notification will be set to this value. Only root@pam can set this parameter. |
| lockwait | integer | no | Maximal time to wait for the global lock (minutes). |
| mailnotification | string | no | Deprecated: use notification targets/matchers instead. Specify when to send a notification mail |
| mailto | string | no | Deprecated: Use notification targets/matchers instead. Comma-separated list of email addresses or users that should receive email notifications. |
| mode | string | no | Backup mode. |
| notes-template | string | no | Template string for generating notes for the backup(s). It can contain variables which will be replaced by their values. Currently supported are {{cluster}}, {{guestname}}, {{node}}, and {{vmid}}, but more might be added in the future. Needs to be a single line, newline and backslash need to be escaped as '\n' and '\\' respectively. |
| notification-mode | string | no | Determine which notification system to use. If set to 'legacy-sendmail', vzdump will consider the mailto/mailnotification parameters and send emails to the specified address(es) via the 'sendmail' command. If set to 'notification-system', a notification will be sent via PVE's notification system, and the mailto and mailnotification will be ignored. If set to 'auto' (default setting), an email will be sent if mailto is set, and the notification system will be used if not. |
| pbs-change-detection-mode | string | no | PBS mode used to detect file changes and switch encoding format for container backups. |
| performance | string | no | Other performance-related settings. |
| pigz | integer | no | Use pigz instead of gzip when N>0. N=1 uses half of cores, N>1 uses N as thread count. |
| pool | string | no | Backup all known guest systems included in the specified pool. |
| protected | boolean | no | If true, mark backup(s) as protected. |
| prune-backups | string | no | Use these retention options instead of those from the storage configuration. |
| quiet | boolean | no | Be quiet. |
| remove | boolean | no | Prune older backups according to 'prune-backups'. |
| script | string | no | Use specified hook script. |
| stdexcludes | boolean | no | Exclude temporary files and logs. |
| stdout | boolean | no | Write tar to stdout, not to a file. |
| stop | boolean | no | Stop running backup jobs on this host. |
| stopwait | integer | no | Maximal time to wait until a guest system is stopped (minutes). |
| storage | string | no | Store resulting file to this storage. |
| tmpdir | string | no | Store temporary files to specified directory. |
| vmid | string | no | The ID of the guest system you want to backup. |
| zstd | integer | no | Zstd threads. N=0 uses half of the available cores, if N is set to a value bigger than 0, N is used as thread count. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "description": "The user needs 'VM.Backup' permissions on any VM, and 'Datastore.AllocateSpace' on the backup storage (and fleecing storage when fleecing is used). The 'tmpdir', 'dumpdir', 'script' and 'job-id' parameters are restricted to the 'root@pam' user. The 'prune-backups' setting requires 'Datastore.Allocate' on the backup storage. The 'bwlimit', 'performance' and 'ionice' parameters require 'Sys.Modify' on '/'.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create backup.",
  "method": "POST",
  "name": "vzdump",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "all": {
        "default": 0,
        "description": "Backup all known guest systems on this host.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "bwlimit": {
        "default": 0,
        "description": "Limit I/O bandwidth (in KiB/s).",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
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
        "type": "string",
        "typetext": "<string>"
      },
      "exclude": {
        "description": "Exclude specified guest systems (assumes --all)",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "exclude-path": {
        "description": "Exclude certain files/directories (shell globs). Paths starting with '/' are anchored to the container's root, other paths match relative to each subdirectory.",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "fleecing": {
        "description": "Options for backup fleecing (VM only).",
        "format": "backup-fleecing",
        "optional": 1,
        "type": "string",
        "typetext": "[[enabled=]<1|0>] [,storage=<storage ID>]"
      },
      "ionice": {
        "default": 7,
        "description": "Set IO priority when using the BFQ scheduler. For snapshot and suspend mode backups of VMs, this only affects the compressor. A value of 8 means the idle priority is used, otherwise the best-effort priority is used with the specified value.",
        "maximum": 8,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 8)"
      },
      "job-id": {
        "description": "The ID of the backup job. If set, the 'backup-job' metadata field of the backup notification will be set to this value. Only root@pam can set this parameter.",
        "maxLength": 50,
        "optional": 1,
        "pattern": "\\S+",
        "type": "string"
      },
      "lockwait": {
        "default": 180,
        "description": "Maximal time to wait for the global lock (minutes).",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
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
        "type": "string",
        "typetext": "<string>"
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
        "type": "string",
        "typetext": "<string>"
      },
      "notes-template": {
        "description": "Template string for generating notes for the backup(s). It can contain variables which will be replaced by their values. Currently supported are {{cluster}}, {{guestname}}, {{node}}, and {{vmid}}, but more might be added in the future. Needs to be a single line, newline and backslash need to be escaped as '\\n' and '\\\\' respectively.",
        "maxLength": 1024,
        "optional": 1,
        "requires": "storage",
        "type": "string",
        "typetext": "<string>"
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
        "type": "string",
        "typetext": "[max-workers=<integer>] [,pbs-entries-max=<integer>]"
      },
      "pigz": {
        "default": 0,
        "description": "Use pigz instead of gzip when N>0. N=1 uses half of cores, N>1 uses N as thread count.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "pool": {
        "description": "Backup all known guest systems included in the specified pool.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "protected": {
        "description": "If true, mark backup(s) as protected.",
        "optional": 1,
        "requires": "storage",
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "prune-backups": {
        "default": "keep-all=1",
        "description": "Use these retention options instead of those from the storage configuration.",
        "format": "prune-backups",
        "optional": 1,
        "type": "string",
        "typetext": "[keep-all=<1|0>] [,keep-daily=<N>] [,keep-hourly=<N>] [,keep-last=<N>] [,keep-monthly=<N>] [,keep-weekly=<N>] [,keep-yearly=<N>]"
      },
      "quiet": {
        "default": 0,
        "description": "Be quiet.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "remove": {
        "default": 1,
        "description": "Prune older backups according to 'prune-backups'.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "script": {
        "description": "Use specified hook script.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "stdexcludes": {
        "default": 1,
        "description": "Exclude temporary files and logs.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "stdout": {
        "description": "Write tar to stdout, not to a file.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "stop": {
        "default": 0,
        "description": "Stop running backup jobs on this host.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "stopwait": {
        "default": 10,
        "description": "Maximal time to wait until a guest system is stopped (minutes).",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "storage": {
        "description": "Store resulting file to this storage.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string",
        "typetext": "<storage ID>"
      },
      "tmpdir": {
        "description": "Store temporary files to specified directory.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "The ID of the guest system you want to backup.",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "zstd": {
        "default": 1,
        "description": "Zstd threads. N=0 uses half of the available cores, if N is set to a value bigger than 0, N is used as thread count.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'VM.Backup' permissions on any VM, and 'Datastore.AllocateSpace' on the backup storage (and fleecing storage when fleecing is used). The 'tmpdir', 'dumpdir', 'script' and 'job-id' parameters are restricted to the 'root@pam' user. The 'prune-backups' setting requires 'Datastore.Allocate' on the backup storage. The 'bwlimit', 'performance' and 'ionice' parameters require 'Sys.Modify' on '/'.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
