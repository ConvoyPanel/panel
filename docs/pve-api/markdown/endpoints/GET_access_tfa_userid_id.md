# GET /access/tfa/{userid}/{id}

Fetch a requested TFA entry if present.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | A TFA entry id. |
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

None.

## Returns

```json
{
  "description": "TFA Entry.",
  "properties": {
    "created": {
      "description": "Creation time of this entry as unix epoch.",
      "type": "integer"
    },
    "description": {
      "description": "User chosen description for this entry.",
      "type": "string"
    },
    "enable": {
      "default": 1,
      "description": "Whether this TFA entry is currently enabled.",
      "optional": 1,
      "type": "boolean"
    },
    "id": {
      "description": "The id used to reference this entry.",
      "type": "string"
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
  "description": "Fetch a requested TFA entry if present.",
  "method": "GET",
  "name": "get_tfa_entry",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "A TFA entry id.",
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
          "User.Modify",
          "Sys.Audit"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "description": "TFA Entry.",
    "properties": {
      "created": {
        "description": "Creation time of this entry as unix epoch.",
        "type": "integer"
      },
      "description": {
        "description": "User chosen description for this entry.",
        "type": "string"
      },
      "enable": {
        "default": 1,
        "description": "Whether this TFA entry is currently enabled.",
        "optional": 1,
        "type": "boolean"
      },
      "id": {
        "description": "The id used to reference this entry.",
        "type": "string"
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
      }
    },
    "type": "object"
  }
}
```
