# PUT /access/users/{userid}/unlock-tfa

Unlock a user's TFA authentication.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

None.

## Returns

```json
{
  "type": "boolean"
}
```

## Permissions

```json
{
  "check": [
    "userid-group",
    [
      "User.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Unlock a user's TFA authentication.",
  "method": "PUT",
  "name": "unlock_tfa",
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
      "userid-group",
      [
        "User.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "boolean"
  }
}
```
