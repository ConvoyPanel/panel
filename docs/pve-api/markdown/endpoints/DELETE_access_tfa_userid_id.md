# DELETE /access/tfa/{userid}/{id}

Delete a TFA entry by ID.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | A TFA entry id. |
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
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
  "description": "Delete a TFA entry by ID.",
  "method": "DELETE",
  "name": "delete_tfa",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
