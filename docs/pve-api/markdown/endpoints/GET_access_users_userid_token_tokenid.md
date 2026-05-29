# GET /access/users/{userid}/token/{tokenid}

Get specific API token information.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| tokenid | string | yes | User-specific token identifier. |
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

None.

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
    "privsep": {
      "default": 1,
      "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
      "optional": 1,
      "type": "boolean"
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
  "description": "Get specific API token information.",
  "method": "GET",
  "name": "read_token",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
      "privsep": {
        "default": 1,
        "description": "Restrict API token privileges with separate ACLs (default), or give full privileges of corresponding user.",
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
