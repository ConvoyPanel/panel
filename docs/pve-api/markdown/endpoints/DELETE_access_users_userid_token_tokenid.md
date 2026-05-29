# DELETE /access/users/{userid}/token/{tokenid}

Remove API token for a specific user.

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
  "type": "null"
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
  "description": "Remove API token for a specific user.",
  "method": "DELETE",
  "name": "remove_token",
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
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
