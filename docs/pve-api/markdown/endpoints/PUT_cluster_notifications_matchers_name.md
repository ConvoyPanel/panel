# PUT /cluster/notifications/matchers/{name}

Update existing matcher

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Name of the matcher. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| comment | string | no | Comment |
| delete | array | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable | boolean | no | Disable this matcher |
| invert-match | boolean | no | Invert match of the whole matcher |
| match-calendar | array | no | Match notification timestamp |
| match-field | array | no | Metadata fields to match (regex or exact match). Must be in the form (regex\|exact):<field>=<value> |
| match-severity | array | no | Notification severities to match |
| mode | string | no | Choose between 'all' and 'any' for when multiple properties are specified |
| target | array | no | Targets to notify on match |

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
    "perm",
    "/mapping/notifications",
    [
      "Mapping.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update existing matcher",
  "method": "PUT",
  "name": "update_matcher",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
        "description": "Disable this matcher",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "invert-match": {
        "description": "Invert match of the whole matcher",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "match-calendar": {
        "description": "Match notification timestamp",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "match-field": {
        "description": "Metadata fields to match (regex or exact match). Must be in the form (regex|exact):<field>=<value>",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "match-severity": {
        "description": "Notification severities to match",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
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
        "type": "string",
        "typetext": "<string>"
      },
      "target": {
        "description": "Targets to notify on match",
        "items": {
          "format": "pve-configid",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
