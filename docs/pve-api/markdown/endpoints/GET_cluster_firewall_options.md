# GET /cluster/firewall/options

Get Firewall options.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "ebtables": {
      "default": 1,
      "description": "Enable ebtables rules cluster wide.",
      "optional": 1,
      "type": "boolean"
    },
    "enable": {
      "default": 0,
      "description": "Enable or disable the firewall cluster wide.",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
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
  "description": "Get Firewall options.",
  "method": "GET",
  "name": "get_options",
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
    "properties": {
      "ebtables": {
        "default": 1,
        "description": "Enable ebtables rules cluster wide.",
        "optional": 1,
        "type": "boolean"
      },
      "enable": {
        "default": 0,
        "description": "Enable or disable the firewall cluster wide.",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
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
    },
    "type": "object"
  }
}
```
