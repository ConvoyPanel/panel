# POST /cluster/notifications/endpoints/sendmail

Create a new sendmail endpoint

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the endpoint. |
| author | string | no | Author of the mail |
| comment | string | no | Comment |
| disable | boolean | no | Disable this target |
| from-address | string | no | `From` address for the mail |
| mailto | array | no | List of email recipients |
| mailto-user | array | no | List of users |

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
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "or",
      [
        "perm",
        "/",
        [
          "Sys.Audit",
          "Sys.Modify"
        ]
      ],
      [
        "perm",
        "/",
        [
          "Sys.AccessNetwork"
        ]
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a new sendmail endpoint",
  "method": "POST",
  "name": "create_sendmail_endpoint",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "author": {
        "description": "Author of the mail",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "comment": {
        "description": "Comment",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "disable": {
        "default": 0,
        "description": "Disable this target",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "from-address": {
        "description": "`From` address for the mail",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "mailto": {
        "description": "List of email recipients",
        "items": {
          "format": "email-or-username",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "mailto-user": {
        "description": "List of users",
        "items": {
          "format": "pve-userid",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "name": {
        "description": "The name of the endpoint.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "and",
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "or",
        [
          "perm",
          "/",
          [
            "Sys.Audit",
            "Sys.Modify"
          ]
        ],
        [
          "perm",
          "/",
          [
            "Sys.AccessNetwork"
          ]
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
