# POST /access/users

Create new user.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |
| comment | string | no |  |
| email | string | no |  |
| enable | boolean | no | Enable the account (default). You can set this to '0' to disable the account |
| expire | integer | no | Account expiration date (seconds since epoch). '0' means no expiration date. |
| firstname | string | no |  |
| groups | string | no |  |
| keys | string | no | Keys for two factor auth (yubico). |
| lastname | string | no |  |
| password | string | no | Initial password. |

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
    "and",
    [
      "userid-param",
      "Realm.AllocateUser"
    ],
    [
      "userid-group",
      [
        "User.Modify"
      ],
      "groups_param",
      "create"
    ]
  ],
  "description": "You need 'Realm.AllocateUser' on '/access/realm/<realm>' on the realm of user <userid>, and 'User.Modify' permissions to '/access/groups/<group>' for any group specified (or 'User.Modify' on '/access/groups' if you pass no groups."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create new user.",
  "method": "POST",
  "name": "create_user",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "maxLength": 2048,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "email": {
        "format": "email-opt",
        "maxLength": 254,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "enable": {
        "default": 1,
        "description": "Enable the account (default). You can set this to '0' to disable the account",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "expire": {
        "description": "Account expiration date (seconds since epoch). '0' means no expiration date.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "firstname": {
        "maxLength": 1024,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "groups": {
        "format": "pve-groupid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "Initial password.",
        "maxLength": 64,
        "minLength": 8,
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
      "and",
      [
        "userid-param",
        "Realm.AllocateUser"
      ],
      [
        "userid-group",
        [
          "User.Modify"
        ],
        "groups_param",
        "create"
      ]
    ],
    "description": "You need 'Realm.AllocateUser' on '/access/realm/<realm>' on the realm of user <userid>, and 'User.Modify' permissions to '/access/groups/<group>' for any group specified (or 'User.Modify' on '/access/groups' if you pass no groups."
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
