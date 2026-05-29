# GET /nodes/{node}/services/{service}/state

Read service properties

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| service | string | yes | Service ID |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "active-state": {
      "description": "Current state of the service process (systemd ActiveState).",
      "enum": [
        "active",
        "inactive",
        "failed",
        "activating",
        "deactivating",
        "maintenance",
        "reloading",
        "refreshing",
        "unknown"
      ],
      "type": "string"
    },
    "desc": {
      "description": "Description of the service.",
      "type": "string"
    },
    "name": {
      "description": "Short identifier for the service (e.g., \"pveproxy\").",
      "type": "string"
    },
    "service": {
      "description": "Systemd unit name (e.g., pveproxy).",
      "type": "string"
    },
    "state": {
      "description": "Execution status of the service (systemd SubState).",
      "enum": [
        "dead",
        "condition",
        "start-pre",
        "start",
        "start-post",
        "running",
        "exited",
        "reload",
        "reload-signal",
        "reload-notify",
        "mounting",
        "stop",
        "stop-watchdog",
        "stop-sigterm",
        "stop-sigkill",
        "stop-post",
        "final-watchdog",
        "final-sigterm",
        "final-sigkill",
        "failed",
        "dead-before-auto-restart",
        "failed-before-auto-restart",
        "dead-resources-pinned",
        "auto-restart",
        "auto-restart-queued",
        "cleaning",
        "unknown"
      ],
      "type": "string"
    },
    "unit-state": {
      "description": "Whether the service is enabled (systemd UnitFileState).",
      "enum": [
        "enabled",
        "enabled-runtime",
        "linked",
        "linked-runtime",
        "alias",
        "masked",
        "masked-runtime",
        "static",
        "disabled",
        "indirect",
        "generated",
        "transient",
        "bad",
        "not-found",
        "unknown"
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
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read service properties",
  "method": "GET",
  "name": "service_state",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "service": {
        "description": "Service ID",
        "enum": [
          "chrony",
          "corosync",
          "cron",
          "ksmtuned",
          "lxcfs",
          "postfix",
          "proxmox-firewall",
          "pve-cluster",
          "pve-firewall",
          "pve-ha-crm",
          "pve-ha-lrm",
          "pve-lxc-syscalld",
          "pvedaemon",
          "pvefw-logger",
          "pveproxy",
          "pvescheduler",
          "pvestatd",
          "qmeventd",
          "spiceproxy",
          "sshd",
          "syslog",
          "systemd-journald",
          "systemd-timesyncd"
        ],
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "active-state": {
        "description": "Current state of the service process (systemd ActiveState).",
        "enum": [
          "active",
          "inactive",
          "failed",
          "activating",
          "deactivating",
          "maintenance",
          "reloading",
          "refreshing",
          "unknown"
        ],
        "type": "string"
      },
      "desc": {
        "description": "Description of the service.",
        "type": "string"
      },
      "name": {
        "description": "Short identifier for the service (e.g., \"pveproxy\").",
        "type": "string"
      },
      "service": {
        "description": "Systemd unit name (e.g., pveproxy).",
        "type": "string"
      },
      "state": {
        "description": "Execution status of the service (systemd SubState).",
        "enum": [
          "dead",
          "condition",
          "start-pre",
          "start",
          "start-post",
          "running",
          "exited",
          "reload",
          "reload-signal",
          "reload-notify",
          "mounting",
          "stop",
          "stop-watchdog",
          "stop-sigterm",
          "stop-sigkill",
          "stop-post",
          "final-watchdog",
          "final-sigterm",
          "final-sigkill",
          "failed",
          "dead-before-auto-restart",
          "failed-before-auto-restart",
          "dead-resources-pinned",
          "auto-restart",
          "auto-restart-queued",
          "cleaning",
          "unknown"
        ],
        "type": "string"
      },
      "unit-state": {
        "description": "Whether the service is enabled (systemd UnitFileState).",
        "enum": [
          "enabled",
          "enabled-runtime",
          "linked",
          "linked-runtime",
          "alias",
          "masked",
          "masked-runtime",
          "static",
          "disabled",
          "indirect",
          "generated",
          "transient",
          "bad",
          "not-found",
          "unknown"
        ],
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
