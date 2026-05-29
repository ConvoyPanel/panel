# PUT /cluster/notifications/endpoints/smtp/{name}

Update existing smtp endpoint

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the endpoint. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| author | string | no | Author of the mail. Defaults to 'Proxmox VE'. |
| comment | string | no | Comment |
| delete | array | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable | boolean | no | Disable this target |
| from-address | string | no | `From` address for the mail |
| mailto | array | no | List of email recipients |
| mailto-user | array | no | List of users |
| mode | string | no | Determine which encryption method shall be used for the connection. |
| password | string | no | Password for SMTP authentication |
| port | integer | no | The port to be used. Defaults to 465 for TLS based connections, 587 for STARTTLS based connections and port 25 for insecure plain-text connections. |
| server | string | no | The address of the SMTP server. |
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
  "description": "Update existing smtp endpoint",
  "method": "PUT",
  "name": "update_smtp_endpoint",
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
      "delete": {
        "description": "A list of settings you want to delete.",
        "items": {
          "format": "pve-configid",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
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
        "optional": 1,
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
