# DELETE /access/users/{userid}

Delete user.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
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
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete user.",
  "method": "DELETE",
  "name": "delete_user",
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
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
