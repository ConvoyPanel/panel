# PUT /cluster/firewall/options

Set Firewall options.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| ebtables | boolean | no | Enable ebtables rules cluster wide. |
| enable | integer | no | Enable or disable the firewall cluster wide. |
| log_ratelimit | string | no | Log ratelimiting settings |
| policy_forward | string | no | Forward policy. |
| policy_in | string | no | Input policy. |
| policy_out | string | no | Output policy. |

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
      "Sys.Modify"
    ]
  ]
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
      "ebtables": {
        "default": 1,
        "description": "Enable ebtables rules cluster wide.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "enable": {
        "default": 0,
        "description": "Enable or disable the firewall cluster wide.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "log_ratelimit": {
        "description": "Log ratelimiting settings",
        "format": {
          "burst": {
            "default": 5,
            "description": "Initial burst of packages which will always get logged before the rate is applied",
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          },
          "enable": {
            "default": "1",
            "default_key": 1,
            "description": "Enable or disable log rate limiting",
            "type": "boolean"
          },
          "rate": {
            "default": "1/second",
            "description": "Frequency with which the burst bucket gets refilled",
            "format_description": "rate",
            "optional": 1,
            "pattern": "[1-9][0-9]*\\/(second|minute|hour|day)",
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[enable=]<1|0> [,burst=<integer>] [,rate=<rate>]"
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
      "policy_in": {
        "description": "Input policy.",
        "enum": [
          "ACCEPT",
          "REJECT",
          "DROP"
        ],
        "optional": 1,
        "type": "string"
      },
      "policy_out": {
        "description": "Output policy.",
        "enum": [
          "ACCEPT",
          "REJECT",
          "DROP"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
