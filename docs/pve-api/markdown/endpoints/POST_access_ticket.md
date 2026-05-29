# POST /access/ticket

Create or verify authentication ticket.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| password | string | yes | The secret password. This can also be a valid ticket. |
| username | string | yes | User name |
| new-format | boolean | no | This parameter is now ignored and assumed to be 1. |
| otp | string | no | One-time password for Two-factor authentication. |
| path | string | no | Verify ticket, and check if user have access 'privs' on 'path' |
| privs | string | no | Verify ticket, and check if user have access 'privs' on 'path' |
| realm | string | no | You can optionally pass the realm using this parameter. Normally the realm is simply added to the username <username>@<realm>. |
| tfa-challenge | string | no | The signed TFA challenge string the user wants to respond to. |

## Returns

```json
{
  "properties": {
    "CSRFPreventionToken": {
      "optional": 1,
      "type": "string"
    },
    "clustername": {
      "optional": 1,
      "type": "string"
    },
    "ticket": {
      "optional": 1,
      "type": "string"
    },
    "username": {
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "You need to pass valid credientials.",
  "user": "world"
}
```

## Raw schema

```json
{
  "allowtoken": 0,
  "description": "Create or verify authentication ticket.",
  "method": "POST",
  "name": "create_ticket",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "new-format": {
        "default": 1,
        "description": "This parameter is now ignored and assumed to be 1.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "otp": {
        "description": "One-time password for Two-factor authentication.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "The secret password. This can also be a valid ticket.",
        "type": "string",
        "typetext": "<string>"
      },
      "path": {
        "description": "Verify ticket, and check if user have access 'privs' on 'path'",
        "maxLength": 64,
        "optional": 1,
        "requires": "privs",
        "type": "string",
        "typetext": "<string>"
      },
      "privs": {
        "description": "Verify ticket, and check if user have access 'privs' on 'path'",
        "format": "pve-priv-list",
        "maxLength": 64,
        "optional": 1,
        "requires": "path",
        "type": "string",
        "typetext": "<string>"
      },
      "realm": {
        "description": "You can optionally pass the realm using this parameter. Normally the realm is simply added to the username <username>@<realm>.",
        "format": "pve-realm",
        "maxLength": 32,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "tfa-challenge": {
        "description": "The signed TFA challenge string the user wants to respond to.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "username": {
        "description": "User name",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "You need to pass valid credientials.",
    "user": "world"
  },
  "protected": 1,
  "returns": {
    "properties": {
      "CSRFPreventionToken": {
        "optional": 1,
        "type": "string"
      },
      "clustername": {
        "optional": 1,
        "type": "string"
      },
      "ticket": {
        "optional": 1,
        "type": "string"
      },
      "username": {
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
