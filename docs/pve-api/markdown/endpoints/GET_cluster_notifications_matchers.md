# GET /cluster/notifications/matchers

Returns a list of all matchers

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
        "description": "Disable this matcher",
        "optional": 1,
        "type": "boolean"
      },
      "invert-match": {
        "description": "Invert match of the whole matcher",
        "optional": 1,
        "type": "boolean"
      },
      "match-calendar": {
        "description": "Match notification timestamp",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "match-field": {
        "description": "Metadata fields to match (regex or exact match). Must be in the form (regex|exact):<field>=<value>",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "match-severity": {
        "description": "Notification severities to match",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "mode": {
        "default": "all",
        "description": "Choose between 'all' and 'any' for when multiple properties are specified",
        "enum": [
          "all",
          "any"
        ],
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "Name of the matcher.",
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
      "target": {
        "description": "Targets to notify on match",
        "items": {
          "format": "pve-configid",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
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
  "description": "Returns a list of all matchers",
  "method": "GET",
  "name": "get_matchers",
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
          "description": "Disable this matcher",
          "optional": 1,
          "type": "boolean"
        },
        "invert-match": {
          "description": "Invert match of the whole matcher",
          "optional": 1,
          "type": "boolean"
        },
        "match-calendar": {
          "description": "Match notification timestamp",
          "items": {
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "match-field": {
          "description": "Metadata fields to match (regex or exact match). Must be in the form (regex|exact):<field>=<value>",
          "items": {
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "match-severity": {
          "description": "Notification severities to match",
          "items": {
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "mode": {
          "default": "all",
          "description": "Choose between 'all' and 'any' for when multiple properties are specified",
          "enum": [
            "all",
            "any"
          ],
          "optional": 1,
          "type": "string"
        },
        "name": {
          "description": "Name of the matcher.",
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
        "target": {
          "description": "Targets to notify on match",
          "items": {
            "format": "pve-configid",
            "type": "string"
          },
          "optional": 1,
          "type": "array"
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
