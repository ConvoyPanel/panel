# GET /cluster/ha/resources/{sid}

Read resource configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| sid | string | yes | HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100). |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "auto-rebalance": {
      "default": 1,
      "description": "HA resource may be migrated during automatic rebalancing.",
      "optional": 1,
      "type": "boolean"
    },
    "comment": {
      "description": "Description.",
      "optional": 1,
      "type": "string"
    },
    "digest": {
      "description": "Can be used to prevent concurrent modifications.",
      "type": "string"
    },
    "failback": {
      "default": 1,
      "description": "The HA resource is automatically migrated to the node with the highest priority according to their node affinity rule, if a node with a higher priority than the current node comes online.",
      "optional": 1,
      "type": "boolean"
    },
    "group": {
      "description": "The HA group identifier.",
      "format": "pve-configid",
      "optional": 1,
      "type": "string"
    },
    "max_relocate": {
      "description": "Maximal number of service relocate tries when a service fails to start.",
      "optional": 1,
      "type": "integer"
    },
    "max_restart": {
      "description": "Maximal number of tries to restart the service on a node after its start failed.",
      "optional": 1,
      "type": "integer"
    },
    "sid": {
      "description": "HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100).",
      "format": "pve-ha-resource-or-vm-id",
      "type": "string",
      "typetext": "<type>:<name>"
    },
    "state": {
      "description": "Requested resource state.",
      "enum": [
        "started",
        "stopped",
        "enabled",
        "disabled",
        "ignored"
      ],
      "optional": 1,
      "type": "string"
    },
    "type": {
      "description": "The type of the resources.",
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
  "description": "Read resource configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "sid": {
        "description": "HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100).",
        "format": "pve-ha-resource-or-vm-id",
        "type": "string",
        "typetext": "<type>:<name>"
      }
    }
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
    "properties": {
      "auto-rebalance": {
        "default": 1,
        "description": "HA resource may be migrated during automatic rebalancing.",
        "optional": 1,
        "type": "boolean"
      },
      "comment": {
        "description": "Description.",
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Can be used to prevent concurrent modifications.",
        "type": "string"
      },
      "failback": {
        "default": 1,
        "description": "The HA resource is automatically migrated to the node with the highest priority according to their node affinity rule, if a node with a higher priority than the current node comes online.",
        "optional": 1,
        "type": "boolean"
      },
      "group": {
        "description": "The HA group identifier.",
        "format": "pve-configid",
        "optional": 1,
        "type": "string"
      },
      "max_relocate": {
        "description": "Maximal number of service relocate tries when a service fails to start.",
        "optional": 1,
        "type": "integer"
      },
      "max_restart": {
        "description": "Maximal number of tries to restart the service on a node after its start failed.",
        "optional": 1,
        "type": "integer"
      },
      "sid": {
        "description": "HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100).",
        "format": "pve-ha-resource-or-vm-id",
        "type": "string",
        "typetext": "<type>:<name>"
      },
      "state": {
        "description": "Requested resource state.",
        "enum": [
          "started",
          "stopped",
          "enabled",
          "disabled",
          "ignored"
        ],
        "optional": 1,
        "type": "string"
      },
      "type": {
        "description": "The type of the resources.",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
