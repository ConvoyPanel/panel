# GET /access/tfa

List TFA configurations of users.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "description": "The list tuples of user and TFA entries.",
  "items": {
    "properties": {
      "entries": {
        "items": {
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
        },
        "type": "array"
      },
      "tfa-locked-until": {
        "description": "Contains a timestamp until when a user is locked out of 2nd factors.",
        "optional": 1,
        "type": "integer"
      },
      "totp-locked": {
        "description": "True if the user is currently locked out of TOTP factors.",
        "optional": 1,
        "type": "boolean"
      },
      "userid": {
        "description": "User this entry belongs to.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{userid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Returns all or just the logged-in user, depending on privileges.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List TFA configurations of users.",
  "method": "GET",
  "name": "list_tfa",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "description": "Returns all or just the logged-in user, depending on privileges.",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "description": "The list tuples of user and TFA entries.",
    "items": {
      "properties": {
        "entries": {
          "items": {
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
          },
          "type": "array"
        },
        "tfa-locked-until": {
          "description": "Contains a timestamp until when a user is locked out of 2nd factors.",
          "optional": 1,
          "type": "integer"
        },
        "totp-locked": {
          "description": "True if the user is currently locked out of TOTP factors.",
          "optional": 1,
          "type": "boolean"
        },
        "userid": {
          "description": "User this entry belongs to.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{userid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
