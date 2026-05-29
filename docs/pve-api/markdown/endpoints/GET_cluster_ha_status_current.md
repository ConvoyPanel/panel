# GET /cluster/ha/status/current

Get HA manager status.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "armed-state": {
        "description": "For type 'fencing'. Whether HA is armed, on standby, disarming or disarmed.",
        "enum": [
          "armed",
          "standby",
          "disarming",
          "disarmed"
        ],
        "optional": 1,
        "type": "string"
      },
      "auto-rebalance": {
        "default": 1,
        "description": "HA resource may be migrated during automatic rebalancing.",
        "optional": 1,
        "type": "boolean"
      },
      "crm_state": {
        "description": "For type 'service'. Service state as seen by the CRM.",
        "optional": 1,
        "type": "string"
      },
      "failback": {
        "default": 1,
        "description": "The HA resource is automatically migrated to the node with the highest priority according to their node affinity rule, if a node with a higher priority than the current node comes online.",
        "optional": 1,
        "type": "boolean"
      },
      "id": {
        "description": "Status entry ID (quorum, master, lrm:<node>, service:<sid>).",
        "type": "string"
      },
      "max_relocate": {
        "description": "For type 'service'.",
        "optional": 1,
        "type": "integer"
      },
      "max_restart": {
        "description": "For type 'service'.",
        "optional": 1,
        "type": "integer"
      },
      "node": {
        "description": "Node associated to status entry.",
        "type": "string"
      },
      "quorate": {
        "description": "For type 'quorum'. Whether the cluster is quorate or not.",
        "optional": 1,
        "type": "boolean"
      },
      "request_state": {
        "description": "For type 'service'. Requested service state.",
        "optional": 1,
        "type": "string"
      },
      "resource_mode": {
        "description": "For type 'fencing'. How resources are handled while disarmed.",
        "enum": [
          "freeze",
          "ignore"
        ],
        "optional": 1,
        "type": "string"
      },
      "sid": {
        "description": "For type 'service'. Service ID.",
        "optional": 1,
        "type": "string"
      },
      "state": {
        "description": "For type 'service'. Verbose service state.",
        "optional": 1,
        "type": "string"
      },
      "status": {
        "description": "Status of the entry (value depends on type).",
        "type": "string"
      },
      "timestamp": {
        "description": "For type 'lrm','master'. Timestamp of the status information.",
        "optional": 1,
        "type": "integer"
      },
      "type": {
        "description": "Type of status entry.",
        "enum": [
          "quorum",
          "master",
          "lrm",
          "service",
          "fencing"
        ]
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
  "check": [
    "perm",
    "/",
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
  "description": "Get HA manager status.",
  "method": "GET",
  "name": "status",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "armed-state": {
          "description": "For type 'fencing'. Whether HA is armed, on standby, disarming or disarmed.",
          "enum": [
            "armed",
            "standby",
            "disarming",
            "disarmed"
          ],
          "optional": 1,
          "type": "string"
        },
        "auto-rebalance": {
          "default": 1,
          "description": "HA resource may be migrated during automatic rebalancing.",
          "optional": 1,
          "type": "boolean"
        },
        "crm_state": {
          "description": "For type 'service'. Service state as seen by the CRM.",
          "optional": 1,
          "type": "string"
        },
        "failback": {
          "default": 1,
          "description": "The HA resource is automatically migrated to the node with the highest priority according to their node affinity rule, if a node with a higher priority than the current node comes online.",
          "optional": 1,
          "type": "boolean"
        },
        "id": {
          "description": "Status entry ID (quorum, master, lrm:<node>, service:<sid>).",
          "type": "string"
        },
        "max_relocate": {
          "description": "For type 'service'.",
          "optional": 1,
          "type": "integer"
        },
        "max_restart": {
          "description": "For type 'service'.",
          "optional": 1,
          "type": "integer"
        },
        "node": {
          "description": "Node associated to status entry.",
          "type": "string"
        },
        "quorate": {
          "description": "For type 'quorum'. Whether the cluster is quorate or not.",
          "optional": 1,
          "type": "boolean"
        },
        "request_state": {
          "description": "For type 'service'. Requested service state.",
          "optional": 1,
          "type": "string"
        },
        "resource_mode": {
          "description": "For type 'fencing'. How resources are handled while disarmed.",
          "enum": [
            "freeze",
            "ignore"
          ],
          "optional": 1,
          "type": "string"
        },
        "sid": {
          "description": "For type 'service'. Service ID.",
          "optional": 1,
          "type": "string"
        },
        "state": {
          "description": "For type 'service'. Verbose service state.",
          "optional": 1,
          "type": "string"
        },
        "status": {
          "description": "Status of the entry (value depends on type).",
          "type": "string"
        },
        "timestamp": {
          "description": "For type 'lrm','master'. Timestamp of the status information.",
          "optional": 1,
          "type": "integer"
        },
        "type": {
          "description": "Type of status entry.",
          "enum": [
            "quorum",
            "master",
            "lrm",
            "service",
            "fencing"
          ]
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
