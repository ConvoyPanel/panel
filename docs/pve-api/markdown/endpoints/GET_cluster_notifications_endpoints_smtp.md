# GET /cluster/notifications/endpoints/smtp

Returns a list of all smtp endpoints

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
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
      "origin": {
        "description": "Show if this entry was created by a user or was built-in",
        "enum": [
          "user-created",
          "builtin",
          "modified-builtin"
        ],
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
  },
  "links": [
    {
      "href": "{name}",
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
  "description": "Returns a list of all smtp endpoints",
  "method": "GET",
  "name": "get_smtp_endpoints",
  "parameters": {
    "additionalProperties": 0
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
    "items": {
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
        "origin": {
          "description": "Show if this entry was created by a user or was built-in",
          "enum": [
            "user-created",
            "builtin",
            "modified-builtin"
          ],
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
    },
    "links": [
      {
        "href": "{name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
