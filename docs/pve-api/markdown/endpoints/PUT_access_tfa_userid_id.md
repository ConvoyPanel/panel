# PUT /access/tfa/{userid}/{id}

Add a TFA entry for a user.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | A TFA entry id. |
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| description | string | no | A description to distinguish multiple entries from one another |
| enable | boolean | no | Whether the entry should be enabled for login. |
| password | string | no | The current password of the user performing the change. |

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
  "allowtoken": 0,
  "description": "Add a TFA entry for a user.",
  "method": "PUT",
  "name": "update_tfa_entry",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "description": {
        "description": "A description to distinguish multiple entries from one another",
        "maxLength": 255,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "enable": {
        "description": "Whether the entry should be enabled for login.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "id": {
        "description": "A TFA entry id.",
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "The current password of the user performing the change.",
        "maxLength": 64,
        "minLength": 5,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
