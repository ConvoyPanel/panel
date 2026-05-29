# GET /cluster/sdn/vnets/{vnet}/firewall/options

Get vnet firewall options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "enable": {
      "default": 0,
      "description": "Enable/disable firewall rules.",
      "optional": 1,
      "type": "boolean"
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
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "Needs SDN.Audit or SDN.Allocate permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get vnet firewall options.",
  "method": "GET",
  "name": "get_options",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
    "description": "Needs SDN.Audit or SDN.Allocate permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "returns": {
    "properties": {
      "enable": {
        "default": 0,
        "description": "Enable/disable firewall rules.",
        "optional": 1,
        "type": "boolean"
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
      }
    },
    "type": "object"
  }
}
```
