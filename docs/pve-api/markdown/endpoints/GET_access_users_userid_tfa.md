# GET /access/users/{userid}/tfa

Get user TFA types (Personal and Realm).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| multiple | boolean | no | Request all entries as an array. |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "realm": {
      "description": "The type of TFA the users realm has set, if any.",
      "enum": [
        "oath",
        "yubico"
      ],
      "optional": 1,
      "type": "string"
    },
    "types": {
      "description": "Array of the user configured TFA types, if any. Only available if 'multiple' was not passed.",
      "items": {
        "description": "A TFA type.",
        "enum": [
          "totp",
          "u2f",
          "yubico",
          "webauthn",
          "recovedry"
        ],
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "user": {
      "description": "The type of TFA the user has set, if any. Only set if 'multiple' was not passed.",
      "enum": [
        "oath",
        "u2f"
      ],
      "optional": 1,
      "type": "string"
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
        "User.Modify",
        "Sys.Audit"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get user TFA types (Personal and Realm).",
  "method": "GET",
  "name": "read_user_tfa_type",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "multiple": {
        "default": 0,
        "description": "Request all entries as an array.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
          "User.Modify",
          "Sys.Audit"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "realm": {
        "description": "The type of TFA the users realm has set, if any.",
        "enum": [
          "oath",
          "yubico"
        ],
        "optional": 1,
        "type": "string"
      },
      "types": {
        "description": "Array of the user configured TFA types, if any. Only available if 'multiple' was not passed.",
        "items": {
          "description": "A TFA type.",
          "enum": [
            "totp",
            "u2f",
            "yubico",
            "webauthn",
            "recovedry"
          ],
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "user": {
        "description": "The type of TFA the user has set, if any. Only set if 'multiple' was not passed.",
        "enum": [
          "oath",
          "u2f"
        ],
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
