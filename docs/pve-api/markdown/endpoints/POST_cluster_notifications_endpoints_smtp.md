# POST /cluster/notifications/endpoints/smtp

Create a new smtp endpoint

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| from-address | string | yes | `From` address for the mail |
| name | string | yes | The name of the endpoint. |
| server | string | yes | The address of the SMTP server. |
| author | string | no | Author of the mail. Defaults to 'Proxmox VE'. |
| comment | string | no | Comment |
| disable | boolean | no | Disable this target |
| mailto | array | no | List of email recipients |
| mailto-user | array | no | List of users |
| mode | string | no | Determine which encryption method shall be used for the connection. |
| password | string | no | Password for SMTP authentication |
| port | integer | no | The port to be used. Defaults to 465 for TLS based connections, 587 for STARTTLS based connections and port 25 for insecure plain-text connections. |
| username | string | no | Username for SMTP authentication |

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
  "description": "Create a new smtp endpoint",
  "method": "POST",
  "name": "create_smtp_endpoint",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "author": {
        "description": "Author of the mail. Defaults to 'Proxmox VE'.",
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
      "mode": {
        "default": "tls",
        "description": "Determine which encryption method shall be used for the connection.",
        "enum": [
          "insecure",
          "starttls",
          "tls"
        ],
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "The name of the endpoint.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "Password for SMTP authentication",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "description": "The port to be used. Defaults to 465 for TLS based connections, 587 for STARTTLS based connections and port 25 for insecure plain-text connections.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "server": {
        "description": "The address of the SMTP server.",
        "type": "string",
        "typetext": "<string>"
      },
      "username": {
        "description": "Username for SMTP authentication",
        "optional": 1,
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
