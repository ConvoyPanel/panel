# GET /cluster/notifications/targets

Returns a list of all entities that can be used as notification targets.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "description": "Comment",
        "optional": 1,
        "type": "string"
      },
      "disable": {
        "default": 0,
        "description": "Show if this target is disabled",
        "optional": 1,
        "type": "boolean"
      },
      "name": {
        "description": "Name of the target.",
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
      "type": {
        "description": "Type of the target.",
        "enum": [
          "sendmail",
          "gotify",
          "smtp",
          "webhook"
        ],
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
    ],
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Use"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Returns a list of all entities that can be used as notification targets.",
  "method": "GET",
  "name": "get_all_targets",
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
      ],
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Use"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "description": "Comment",
          "optional": 1,
          "type": "string"
        },
        "disable": {
          "default": 0,
          "description": "Show if this target is disabled",
          "optional": 1,
          "type": "boolean"
        },
        "name": {
          "description": "Name of the target.",
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
        "type": {
          "description": "Type of the target.",
          "enum": [
            "sendmail",
            "gotify",
            "smtp",
            "webhook"
          ],
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
