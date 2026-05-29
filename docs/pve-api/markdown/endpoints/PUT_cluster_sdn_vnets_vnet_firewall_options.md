# PUT /cluster/sdn/vnets/{vnet}/firewall/options

Set Firewall options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| enable | boolean | no | Enable/disable firewall rules. |
| log_level_forward | string | no | Log level for forwarded traffic. |
| policy_forward | string | no | Forward policy. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "description": "Needs SDN.Allocate permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Set Firewall options.",
  "method": "PUT",
  "name": "set_options",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
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
      "enable": {
        "default": 0,
        "description": "Enable/disable firewall rules.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "log_level_forward": {
        "description": "Log level for forwarded traffic.",
        "enum": [
          "emerg",
          "alert",
          "crit",
          "err",
          "warning",
          "notice",
          "info",
          "debug",
          "nolog"
        ],
        "optional": 1,
        "type": "string"
      },
      "policy_forward": {
        "description": "Forward policy.",
        "enum": [
          "ACCEPT",
          "DROP"
        ],
        "optional": 1,
        "type": "string"
      },
      "vnet": {
        "description": "The SDN vnet object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Needs SDN.Allocate permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
