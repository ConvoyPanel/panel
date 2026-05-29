# GET /access/users/{userid}/token

Get user API tokens.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

None.

## Returns

```json
{
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
  "links": [
    {
      "href": "{tokenid}",
      "rel": "child"
    }
  ],
  "type": "array"
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
  "description": "Get user API tokens.",
  "method": "GET",
  "name": "token_index",
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
    "links": [
      {
        "href": "{tokenid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
