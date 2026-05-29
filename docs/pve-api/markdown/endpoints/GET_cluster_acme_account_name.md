# GET /cluster/acme/account/{name}

Return existing ACME account information.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | no | ACME account config file name. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "account": {
      "optional": 1,
      "renderer": "yaml",
      "type": "object"
    },
    "directory": {
      "description": "URL of ACME CA directory endpoint.",
      "optional": 1,
      "pattern": "^https?://.*",
      "type": "string"
    },
    "location": {
      "optional": 1,
      "type": "string"
    },
    "tos": {
      "optional": 1,
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Return existing ACME account information.",
  "method": "GET",
  "name": "get_account",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "default": "default",
        "description": "ACME account config file name.",
        "format": "pve-configid",
        "format_description": "name",
        "optional": 1,
        "type": "string",
        "typetext": "<name>"
      }
    }
  },
  "protected": 1,
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "account": {
        "optional": 1,
        "renderer": "yaml",
        "type": "object"
      },
      "directory": {
        "description": "URL of ACME CA directory endpoint.",
        "optional": 1,
        "pattern": "^https?://.*",
        "type": "string"
      },
      "location": {
        "optional": 1,
        "type": "string"
      },
      "tos": {
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
