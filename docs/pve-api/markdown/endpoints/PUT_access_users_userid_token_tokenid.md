# PUT /access/users/{userid}/token/{tokenid}

Update API token for a specific user. NOTE: when 'regenerate' is set, the returned token value needs to be stored as it cannot be retrieved afterwards!

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| tokenid | string | yes | User-specific token identifier. |
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| comment | string | no |  |
| delete | string | no | A list of settings you want to delete. |
| expire | integer | no | API token expiration date (seconds since epoch). '0' means no expiration date. |
| privsep | boolean | no | Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user. |
| regenerate | boolean | no | Regenerate the token's secret value. All users of the previous secret will lose access after this operation. |

## Returns

```json
{
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
    "full-tokenid": {
      "description": "The full token id. Only set when 'regenerate' was set.",
      "format_description": "<userid>!<tokenid>",
      "optional": 1,
      "type": "string"
    },
    "privsep": {
      "default": 1,
      "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
      "optional": 1,
      "type": "boolean"
    },
    "value": {
      "description": "API token value used for authentication. Only set when 'regenerate' was set.",
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
  "description": "Update API token for a specific user. NOTE: when 'regenerate' is set, the returned token value needs to be stored as it cannot be retrieved afterwards!",
  "method": "PUT",
  "name": "update_token_info",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
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
      "regenerate": {
        "default": 0,
        "description": "Regenerate the token's secret value. All users of the previous secret will lose access after this operation.",
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
      "full-tokenid": {
        "description": "The full token id. Only set when 'regenerate' was set.",
        "format_description": "<userid>!<tokenid>",
        "optional": 1,
        "type": "string"
      },
      "privsep": {
        "default": 1,
        "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
        "optional": 1,
        "type": "boolean"
      },
      "value": {
        "description": "API token value used for authentication. Only set when 'regenerate' was set.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
