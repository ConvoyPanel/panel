# POST /cluster/ha/resources/{sid}/migrate

Request resource migration (online) to another node.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| sid | string | yes | HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100). |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | Target node. |

## Returns

```json
{
  "properties": {
    "blocking-resources": {
      "description": "HA resources, which are blocking the given HA resource from being migrated to the requested target node.",
      "items": {
        "description": "A blocking HA resource",
        "properties": {
          "cause": {
            "description": "The reason why the HA resource is blocking the migration.",
            "enum": [
              "node-affinity",
              "resource-affinity"
            ],
            "type": "string"
          },
          "sid": {
            "description": "The blocking HA resource id",
            "type": "string"
          }
        },
        "type": "object"
      },
      "optional": 1,
      "type": "array"
    },
    "comigrated-resources": {
      "description": "HA resources, which are migrated to the same requested target node as the given HA resource, because these are in positive affinity with the HA resource.",
      "optional": 1,
      "type": "array"
    },
    "requested-node": {
      "description": "Node, which was requested to be migrated to.",
      "optional": 0,
      "type": "string"
    },
    "sid": {
      "description": "HA resource, which is requested to be migrated.",
      "optional": 0,
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
      "Sys.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Request resource migration (online) to another node.",
  "method": "POST",
  "name": "migrate",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "Target node.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
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
        "Sys.Console"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "properties": {
      "blocking-resources": {
        "description": "HA resources, which are blocking the given HA resource from being migrated to the requested target node.",
        "items": {
          "description": "A blocking HA resource",
          "properties": {
            "cause": {
              "description": "The reason why the HA resource is blocking the migration.",
              "enum": [
                "node-affinity",
                "resource-affinity"
              ],
              "type": "string"
            },
            "sid": {
              "description": "The blocking HA resource id",
              "type": "string"
            }
          },
          "type": "object"
        },
        "optional": 1,
        "type": "array"
      },
      "comigrated-resources": {
        "description": "HA resources, which are migrated to the same requested target node as the given HA resource, because these are in positive affinity with the HA resource.",
        "optional": 1,
        "type": "array"
      },
      "requested-node": {
        "description": "Node, which was requested to be migrated to.",
        "optional": 0,
        "type": "string"
      },
      "sid": {
        "description": "HA resource, which is requested to be migrated.",
        "optional": 0,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
