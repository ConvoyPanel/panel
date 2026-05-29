# POST /access/tfa/{userid}

Add a TFA entry for a user.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | yes | TFA Entry Type. |
| challenge | string | no | When responding to a u2f challenge: the original challenge string |
| description | string | no | A description to distinguish multiple entries from one another |
| password | string | no | The current password of the user performing the change. |
| totp | string | no | A totp URI. |
| value | string | no | The current value for the provided totp URI, or a Webauthn/U2F challenge response |

## Returns

```json
{
  "properties": {
    "challenge": {
      "description": "When adding u2f entries, this contains a challenge the user must respond to in order to finish the registration.",
      "optional": 1,
      "type": "string"
    },
    "id": {
      "description": "The id of a newly added TFA entry.",
      "type": "string"
    },
    "recovery": {
      "description": "When adding recovery codes, this contains the list of codes to be displayed to the user",
      "items": {
        "description": "A recovery entry.",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
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
  "allowtoken": 0,
  "description": "Add a TFA entry for a user.",
  "method": "POST",
  "name": "add_tfa_entry",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "challenge": {
        "description": "When responding to a u2f challenge: the original challenge string",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "description": {
        "description": "A description to distinguish multiple entries from one another",
        "maxLength": 255,
        "optional": 1,
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
      "totp": {
        "description": "A totp URI.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "TFA Entry Type.",
        "enum": [
          "totp",
          "u2f",
          "webauthn",
          "recovery",
          "yubico"
        ],
        "type": "string"
      },
      "userid": {
        "description": "Full User ID, in the `name@realm` format.",
        "format": "pve-userid",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      },
      "value": {
        "description": "The current value for the provided totp URI, or a Webauthn/U2F challenge response",
        "optional": 1,
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
      "challenge": {
        "description": "When adding u2f entries, this contains a challenge the user must respond to in order to finish the registration.",
        "optional": 1,
        "type": "string"
      },
      "id": {
        "description": "The id of a newly added TFA entry.",
        "type": "string"
      },
      "recovery": {
        "description": "When adding recovery codes, this contains the list of codes to be displayed to the user",
        "items": {
          "description": "A recovery entry.",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
