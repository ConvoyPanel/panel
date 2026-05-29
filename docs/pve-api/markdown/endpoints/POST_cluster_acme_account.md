# POST /cluster/acme/account

Register a new ACME account with CA.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| contact | string | yes | Contact email addresses. |
| directory | string | no | URL of ACME CA directory endpoint. |
| eab-hmac-key | string | no | HMAC key for External Account Binding. |
| eab-kid | string | no | Key Identifier for External Account Binding. |
| name | string | no | ACME account config file name. |
| tos_url | string | no | URL of CA TermsOfService - setting this indicates agreement. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Register a new ACME account with CA.",
  "method": "POST",
  "name": "register_account",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "contact": {
        "description": "Contact email addresses.",
        "format": "email-list",
        "type": "string",
        "typetext": "<string>"
      },
      "directory": {
        "default": "https://acme-v02.api.letsencrypt.org/directory",
        "description": "URL of ACME CA directory endpoint.",
        "optional": 1,
        "pattern": "^https?://.*",
        "type": "string"
      },
      "eab-hmac-key": {
        "description": "HMAC key for External Account Binding.",
        "optional": 1,
        "requires": "eab-kid",
        "type": "string",
        "typetext": "<string>"
      },
      "eab-kid": {
        "description": "Key Identifier for External Account Binding.",
        "optional": 1,
        "requires": "eab-hmac-key",
        "type": "string",
        "typetext": "<string>"
      },
      "name": {
        "default": "default",
        "description": "ACME account config file name.",
        "format": "pve-configid",
        "format_description": "name",
        "optional": 1,
        "type": "string",
        "typetext": "<name>"
      },
      "tos_url": {
        "description": "URL of CA TermsOfService - setting this indicates agreement.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "protected": 1,
  "returns": {
    "type": "string"
  }
}
```
