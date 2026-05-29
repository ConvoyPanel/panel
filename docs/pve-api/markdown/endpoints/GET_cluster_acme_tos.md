# GET /cluster/acme/tos

Retrieve ACME TermsOfService URL from CA. Deprecated, please use /cluster/acme/meta.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| directory | string | no | URL of ACME CA directory endpoint. |

## Returns

```json
{
  "description": "ACME TermsOfService URL.",
  "optional": 1,
  "type": "string"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Retrieve ACME TermsOfService URL from CA. Deprecated, please use /cluster/acme/meta.",
  "method": "GET",
  "name": "get_tos",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "directory": {
        "default": "https://acme-v02.api.letsencrypt.org/directory",
        "description": "URL of ACME CA directory endpoint.",
        "optional": 1,
        "pattern": "^https?://.*",
        "type": "string"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "description": "ACME TermsOfService URL.",
    "optional": 1,
    "type": "string"
  }
}
```
