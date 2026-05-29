# GET /cluster/notifications/matchers/{name}

Return a specific matcher

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
  "description": "Return a specific matcher",
  "method": "GET",
  "name": "get_matcher",
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
  }
}
```
