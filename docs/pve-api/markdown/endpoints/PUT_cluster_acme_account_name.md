# PUT /cluster/acme/account/{name}

Update existing ACME account information with CA. Note: not specifying any new account information triggers a refresh.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | no | ACME account config file name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| contact | string | no | Contact email addresses. |

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
  "description": "Update existing ACME account information with CA. Note: not specifying any new account information triggers a refresh.",
  "method": "PUT",
  "name": "update_account",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "contact": {
        "description": "Contact email addresses.",
        "format": "email-list",
        "optional": 1,
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
      }
    }
  },
  "protected": 1,
  "returns": {
    "type": "string"
  }
}
```
