# GET /cluster/notifications/matcher-field-values

Returns known notification metadata fields and their known values

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
        "description": "Additional comment for this value.",
        "optional": 1,
        "type": "string"
      },
      "field": {
        "description": "Field this value belongs to.",
        "type": "string"
      },
      "value": {
        "description": "Notification metadata value known by the system.",
        "type": "string"
      }
    },
    "type": "object"
  },
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
  "description": "Returns known notification metadata fields and their known values",
  "method": "GET",
  "name": "get_matcher_field_values",
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
        "comment": {
          "description": "Additional comment for this value.",
          "optional": 1,
          "type": "string"
        },
        "field": {
          "description": "Field this value belongs to.",
          "type": "string"
        },
        "value": {
          "description": "Notification metadata value known by the system.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
