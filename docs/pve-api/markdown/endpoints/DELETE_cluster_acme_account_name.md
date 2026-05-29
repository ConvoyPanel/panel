# DELETE /cluster/acme/account/{name}

Deactivate existing ACME account at CA.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | no | ACME account config file name. |

## Request parameters

None.

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
  "description": "Deactivate existing ACME account at CA.",
  "method": "DELETE",
  "name": "deactivate_account",
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
    "type": "string"
  }
}
```
