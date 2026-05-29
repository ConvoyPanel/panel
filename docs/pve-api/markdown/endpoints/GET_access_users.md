# GET /access/users

User index.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| enabled | boolean | no | Optional filter for enable property. |
| full | boolean | no | Include group and token information. |

## Returns

```json
{
  "items": {
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
        "format": "pve-groupid-list",
        "optional": 1,
        "type": "string"
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
      "realm-type": {
        "description": "The type of the users realm",
        "format": "pve-realm",
        "optional": 1,
        "type": "string"
      },
      "tfa-locked-until": {
        "description": "Contains a timestamp until when a user is locked out of 2nd factors.",
        "optional": 1,
        "type": "integer"
      },
      "tokens": {
        "items": {
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
            },
            "tokenid": {
              "description": "User-specific token identifier.",
              "pattern": "(?^:[A-Za-z][A-Za-z0-9\\.\\-_]+)",
              "type": "string"
            }
          },
          "type": "object"
        },
        "optional": 1,
        "type": "array"
      },
      "totp-locked": {
        "description": "True if the user is currently locked out of TOTP factors.",
        "optional": 1,
        "type": "boolean"
      },
      "userid": {
        "description": "Full User ID, in the `name@realm` format.",
        "format": "pve-userid",
        "maxLength": 64,
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{userid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "The returned list is restricted to users where you have 'User.Modify' or 'Sys.Audit' permissions on '/access/groups' or on a group the user belongs too. But it always includes the current (authenticated) user.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "User index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "enabled": {
        "description": "Optional filter for enable property.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "full": {
        "default": 0,
        "description": "Include group and token information.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "description": "The returned list is restricted to users where you have 'User.Modify' or 'Sys.Audit' permissions on '/access/groups' or on a group the user belongs too. But it always includes the current (authenticated) user.",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "items": {
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
          "format": "pve-groupid-list",
          "optional": 1,
          "type": "string"
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
        "realm-type": {
          "description": "The type of the users realm",
          "format": "pve-realm",
          "optional": 1,
          "type": "string"
        },
        "tfa-locked-until": {
          "description": "Contains a timestamp until when a user is locked out of 2nd factors.",
          "optional": 1,
          "type": "integer"
        },
        "tokens": {
          "items": {
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
              },
              "tokenid": {
                "description": "User-specific token identifier.",
                "pattern": "(?^:[A-Za-z][A-Za-z0-9\\.\\-_]+)",
                "type": "string"
              }
            },
            "type": "object"
          },
          "optional": 1,
          "type": "array"
        },
        "totp-locked": {
          "description": "True if the user is currently locked out of TOTP factors.",
          "optional": 1,
          "type": "boolean"
        },
        "userid": {
          "description": "Full User ID, in the `name@realm` format.",
          "format": "pve-userid",
          "maxLength": 64,
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{userid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
