# GET /cluster/notifications/endpoints/smtp/{name}

Return a specific smtp endpoint

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "author": {
      "description": "Author of the mail. Defaults to 'Proxmox VE'.",
      "optional": 1,
      "type": "string"
    },
    "comment": {
      "description": "Comment",
      "optional": 1,
      "type": "string"
    },
    "digest": {
      "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
      "maxLength": 64,
      "optional": 1,
      "type": "string"
    },
    "disable": {
      "default": 0,
      "description": "Disable this target",
      "optional": 1,
      "type": "boolean"
    },
    "from-address": {
      "description": "`From` address for the mail",
      "type": "string"
    },
    "mailto": {
      "description": "List of email recipients",
      "items": {
        "format": "email-or-username",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "mailto-user": {
      "description": "List of users",
      "items": {
        "format": "pve-userid",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
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
      "type": "string"
    },
    "port": {
      "description": "The port to be used. Defaults to 465 for TLS based connections, 587 for STARTTLS based connections and port 25 for insecure plain-text connections.",
      "optional": 1,
      "type": "integer"
    },
    "server": {
      "description": "The address of the SMTP server.",
      "type": "string"
    },
    "username": {
      "description": "Username for SMTP authentication",
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
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Audit"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Return a specific smtp endpoint",
  "method": "GET",
  "name": "get_smtp_endpoint",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "or",
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Audit"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "properties": {
      "author": {
        "description": "Author of the mail. Defaults to 'Proxmox VE'.",
        "optional": 1,
        "type": "string"
      },
      "comment": {
        "description": "Comment",
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string"
      },
      "disable": {
        "default": 0,
        "description": "Disable this target",
        "optional": 1,
        "type": "boolean"
      },
      "from-address": {
        "description": "`From` address for the mail",
        "type": "string"
      },
      "mailto": {
        "description": "List of email recipients",
        "items": {
          "format": "email-or-username",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "mailto-user": {
        "description": "List of users",
        "items": {
          "format": "pve-userid",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
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
        "type": "string"
      },
      "port": {
        "description": "The port to be used. Defaults to 465 for TLS based connections, 587 for STARTTLS based connections and port 25 for insecure plain-text connections.",
        "optional": 1,
        "type": "integer"
      },
      "server": {
        "description": "The address of the SMTP server.",
        "type": "string"
      },
      "username": {
        "description": "Username for SMTP authentication",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
