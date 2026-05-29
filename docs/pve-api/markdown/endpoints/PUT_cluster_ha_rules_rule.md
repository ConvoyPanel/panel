# PUT /cluster/ha/rules/{rule}

Update HA rule.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| rule | string | yes | HA rule identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | yes | HA rule type. |
| affinity | string | no | Describes whether the HA resources are supposed to be kept on the same node ('positive'), or are supposed to be kept on separate nodes ('negative'). |
| comment | string | no | HA rule description. |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable | boolean | no | Whether the HA rule is disabled. |
| nodes | string | no | List of cluster node names with optional priority. |
| resources | string | no | List of HA resource IDs. This consists of a list of resource types followed by a resource specific name separated with a colon (example: vm:100,ct:101). |
| strict | boolean | no | Describes whether the node affinity rule is strict or non-strict. |

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
  "description": "Update HA rule.",
  "method": "PUT",
  "name": "update_rule",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "affinity": {
        "description": "Describes whether the HA resources are supposed to be kept on the same node ('positive'), or are supposed to be kept on separate nodes ('negative').",
        "enum": [
          "positive",
          "negative"
        ],
        "instance-types": [
          "resource-affinity"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "type"
      },
      "comment": {
        "description": "HA rule description.",
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
      "disable": {
        "description": "Whether the HA rule is disabled.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "nodes": {
        "description": "List of cluster node names with optional priority.",
        "format": "pve-ha-node-list",
        "instance-types": [
          "node-affinity"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "type",
        "typetext": "<node>[:<pri>]{,<node>[:<pri>]}*",
        "verbose_description": "List of cluster node members, where a priority can be given to each node. A resource will run on the available nodes with the highest priority. If there are more nodes in the highest priority class, the resources will get distributed to those nodes. The priorities have a relative meaning only. The higher the number, the higher the priority."
      },
      "resources": {
        "description": "List of HA resource IDs. This consists of a list of resource types followed by a resource specific name separated with a colon (example: vm:100,ct:101).",
        "format": "pve-ha-resource-id-list",
        "optional": 1,
        "type": "string",
        "typetext": "<type>:<name>{,<type>:<name>}*"
      },
      "rule": {
        "description": "HA rule identifier.",
        "format": "pve-configid",
        "optional": 0,
        "type": "string",
        "typetext": "<string>"
      },
      "strict": {
        "default": 0,
        "description": "Describes whether the node affinity rule is strict or non-strict.",
        "instance-types": [
          "node-affinity"
        ],
        "optional": 1,
        "type": "boolean",
        "type-property": "type",
        "typetext": "<boolean>",
        "verbose_description": "Describes whether the node affinity rule is strict or non-strict.\n\nA non-strict node affinity rule makes resources prefer to be on the defined nodes.\nIf none of the defined nodes are available, the resource may run on any other node.\n\nA strict node affinity rule makes resources be restricted to the defined nodes. If\nnone of the defined nodes are available, the resource will be stopped.\n"
      },
      "type": {
        "description": "HA rule type.",
        "enum": [
          "node-affinity",
          "resource-affinity"
        ],
        "type": "string"
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
