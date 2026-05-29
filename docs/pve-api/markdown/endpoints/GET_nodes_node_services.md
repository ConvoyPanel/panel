# GET /nodes/{node}/services

Service list.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
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
  },
  "links": [
    {
      "href": "{service}",
      "rel": "child"
    }
  ],
  "type": "array"
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
  "description": "Service list.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
    "items": {
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
    },
    "links": [
      {
        "href": "{service}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
