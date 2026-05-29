# PUT /cluster/ha/resources/{sid}

Update resource configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| sid | string | yes | HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100). |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| auto-rebalance | boolean | no | HA resource may be migrated during automatic rebalancing |
| comment | string | no | Description. |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| failback | boolean | no | Automatically migrate HA resource to the node with the highest priority according to their node affinity  rules, if a node with a higher priority than the current node comes online. |
| group | string | no | The HA group identifier. |
| max_relocate | integer | no | Maximal number of resource relocate tries when a resource fails to start. |
| max_restart | integer | no | Maximal number of tries to restart the resource on a node after its start failed. When reached, the HA manager will try to relocate the resource to an eligible node. |
| state | string | no | Requested resource state. |

## Returns

```json
{
  "type": "null"
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
  "description": "Update resource configuration.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "auto-rebalance": {
        "default": 1,
        "description": "HA resource may be migrated during automatic rebalancing",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "comment": {
        "description": "Description.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "failback": {
        "default": 1,
        "description": "Automatically migrate HA resource to the node with the highest priority according to their node affinity  rules, if a node with a higher priority than the current node comes online.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "group": {
        "description": "The HA group identifier.",
        "format": "pve-configid",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "max_relocate": {
        "default": 1,
        "description": "Maximal number of resource relocate tries when a resource fails to start.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "max_restart": {
        "default": 1,
        "description": "Maximal number of tries to restart the resource on a node after its start failed. When reached, the HA manager will try to relocate the resource to an eligible node.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "sid": {
        "description": "HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100).",
        "format": "pve-ha-resource-or-vm-id",
        "type": "string",
        "typetext": "<type>:<name>"
      },
      "state": {
        "default": "started",
        "description": "Requested resource state.",
        "enum": [
          "started",
          "stopped",
          "enabled",
          "disabled",
          "ignored"
        ],
        "optional": 1,
        "type": "string",
        "verbose_description": "Requested resource state. The CRM reads this state and acts accordingly.\nPlease note that `enabled` is just an alias for `started`.\n\n`started`;;\n\nThe CRM tries to start the resource. Service state is\nset to `started` after successful start. On node failures, or when start\nfails, it tries to recover the resource.  If everything fails, service\nstate it set to `error`.\n\n`stopped`;;\n\nThe CRM tries to keep the resource in `stopped` state, but it\nstill tries to relocate the resources on node failures.\n\n`disabled`;;\n\nThe CRM tries to put the resource in `stopped` state, but does not try\nto relocate the resources on node failures. The main purpose of this\nstate is error recovery, because it is the only way to move a resource out\nof the `error` state.\n\n`ignored`;;\n\nThe resource gets removed from the manager status and so the CRM and the LRM do\nnot touch the resource anymore. All {pve} API calls affecting this resource\nwill be executed, directly bypassing the HA stack. CRM commands will be thrown\naway while the resource is in this state. The resource will not get relocated\non node failures.\n\n"
      }
    },
    "type": "object"
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
    "type": "null"
  }
}
```
