# PUT /access/password

Change user password.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| password | string | yes | The new password. |
| userid | string | yes | Full User ID, in the `name@realm` format. |
| confirmation-password | string | no | The current password of the user performing the change. |

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
      "and",
      [
        "userid-param",
        "Realm.AllocateUser"
      ],
      [
        "userid-group",
        [
          "User.Modify"
        ]
      ]
    ]
  ],
  "description": "Each user is allowed to change their own password. A user can change the password of another user if they have 'Realm.AllocateUser' (on the realm of user <userid>) and 'User.Modify' permission on /access/groups/<group> on a group where user <userid> is member of. For the PAM realm, a password change does not take  effect cluster-wide, but only applies to the local node."
}
```

## Raw schema

```json
{
  "allowtoken": 0,
  "description": "Change user password.",
  "method": "PUT",
  "name": "change_password",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "confirmation-password": {
        "description": "The current password of the user performing the change.",
        "maxLength": 64,
        "minLength": 5,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "The new password.",
        "maxLength": 64,
        "minLength": 8,
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
        "and",
        [
          "userid-param",
          "Realm.AllocateUser"
        ],
        [
          "userid-group",
          [
            "User.Modify"
          ]
        ]
      ]
    ],
    "description": "Each user is allowed to change their own password. A user can change the password of another user if they have 'Realm.AllocateUser' (on the realm of user <userid>) and 'User.Modify' permission on /access/groups/<group> on a group where user <userid> is member of. For the PAM realm, a password change does not take  effect cluster-wide, but only applies to the local node."
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
