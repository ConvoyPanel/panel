# DELETE /cluster/ha/resources/{sid}

Delete resource configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| sid | string | yes | HA resource ID. This consists of a resource type followed by a resource specific name, separated with colon (example: vm:100 / ct:100). For virtual machines and containers, you can simply use the VM or CT id as a shortcut (example: 100). |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| purge | boolean | no | Remove this resource from rules that reference it, deleting the rule if this resource is the only resource in the rule |

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
  "description": "Delete resource configuration.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "purge": {
        "default": 1,
        "description": "Remove this resource from rules that reference it, deleting the rule if this resource is the only resource in the rule",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "type": "null"
  }
}
```
