# POST /access/users/{userid}/token/{tokenid}

Generate a new API token for a specific user. NOTE: returns API token value, which needs to be stored as it cannot be retrieved afterwards!

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| tokenid | string | yes | User-specific token identifier. |
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| comment | string | no |  |
| expire | integer | no | API token expiration date (seconds since epoch). '0' means no expiration date. |
| privsep | boolean | no | Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user. |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "full-tokenid": {
      "description": "The full token id.",
      "format_description": "<userid>!<tokenid>",
      "type": "string"
    },
    "info": {
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
    "value": {
      "description": "API token value used for authentication.",
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
    "or",
    [
      "userid-param",
      "self"
    ],
    [
      "userid-group",
      [
        "User.Modify"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Generate a new API token for a specific user. NOTE: returns API token value, which needs to be stored as it cannot be retrieved afterwards!",
  "method": "POST",
  "name": "generate_token",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "expire": {
        "default": "same as user",
        "description": "API token expiration date (seconds since epoch). '0' means no expiration date.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "privsep": {
        "default": 1,
        "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "tokenid": {
        "description": "User-specific token identifier.",
        "pattern": "(?^:[A-Za-z][A-Za-z0-9\\.\\-_]+)",
        "type": "string"
      },
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
      "or",
      [
        "userid-param",
        "self"
      ],
      [
        "userid-group",
        [
          "User.Modify"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "full-tokenid": {
        "description": "The full token id.",
        "format_description": "<userid>!<tokenid>",
        "type": "string"
      },
      "info": {
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
      "value": {
        "description": "API token value used for authentication.",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
