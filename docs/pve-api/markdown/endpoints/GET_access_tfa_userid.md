# GET /access/tfa/{userid}

List TFA configurations of users.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| userid | string | yes | Full User ID, in the `name@realm` format. |

## Request parameters

None.

## Returns

```json
{
  "description": "A list of the user's TFA entries.",
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
  "links": [
    {
      "href": "{id}",
      "rel": "child"
    }
  ],
  "type": "array"
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
  "description": "List TFA configurations of users.",
  "method": "GET",
  "name": "list_user_tfa",
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
    "description": "A list of the user's TFA entries.",
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
    "links": [
      {
        "href": "{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
