# GET /access/users/{userid}

Get user configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "comment": {
      "maxLength": 2048,
      "optional": 1,
      "type": "string"
    },
    "email": {
      "format": "email-opt",
      "maxLength": 254,
      "optional": 1,
      "type": "string"
    },
    "enable": {
      "default": 1,
      "description": "Enable the account (default). You can set this to '0' to disable the account",
      "optional": 1,
      "type": "boolean"
    },
    "expire": {
      "description": "Account expiration date (seconds since epoch). '0' means no expiration date.",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "firstname": {
      "maxLength": 1024,
      "optional": 1,
      "type": "string"
    },
    "groups": {
      "items": {
        "format": "pve-groupid",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "keys": {
      "description": "Keys for two factor auth (yubico).",
      "optional": 1,
      "pattern": "[0-9a-zA-Z!=]{0,4096}",
      "type": "string"
    },
    "lastname": {
      "maxLength": 1024,
      "optional": 1,
      "type": "string"
    },
    "tokens": {
      "additionalProperties": {
        "properties": {
          "comment": {
            "optional": 1,
            "type": "string"
          },
          "expire": {
            "default": "same as user",
            "description": "API token expiration date (seconds since epoch). '0' means no expiration date.",
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          },
          "privsep": {
            "default": 1,
            "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
            "optional": 1,
            "type": "boolean"
          }
        },
        "type": "object"
      },
      "optional": 1,
      "type": "object"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "userid-group",
    [
      "User.Modify",
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get user configuration.",
  "method": "GET",
  "name": "read_user",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "userid": {
        "description": "Full User ID, in the `name@realm` format.",
        "format": "pve-userid",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "userid-group",
      [
        "User.Modify",
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "maxLength": 2048,
        "optional": 1,
        "type": "string"
      },
      "email": {
        "format": "email-opt",
        "maxLength": 254,
        "optional": 1,
        "type": "string"
      },
      "enable": {
        "default": 1,
        "description": "Enable the account (default). You can set this to '0' to disable the account",
        "optional": 1,
        "type": "boolean"
      },
      "expire": {
        "description": "Account expiration date (seconds since epoch). '0' means no expiration date.",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "firstname": {
        "maxLength": 1024,
        "optional": 1,
        "type": "string"
      },
      "groups": {
        "items": {
          "format": "pve-groupid",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "keys": {
        "description": "Keys for two factor auth (yubico).",
        "optional": 1,
        "pattern": "[0-9a-zA-Z!=]{0,4096}",
        "type": "string"
      },
      "lastname": {
        "maxLength": 1024,
        "optional": 1,
        "type": "string"
      },
      "tokens": {
        "additionalProperties": {
          "properties": {
            "comment": {
              "optional": 1,
              "type": "string"
            },
            "expire": {
              "default": "same as user",
              "description": "API token expiration date (seconds since epoch). '0' means no expiration date.",
              "minimum": 0,
              "optional": 1,
              "type": "integer"
            },
            "privsep": {
              "default": 1,
              "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
              "optional": 1,
              "type": "boolean"
            }
          },
          "type": "object"
        },
        "optional": 1,
        "type": "object"
      }
    },
    "type": "object"
  }
}
```
