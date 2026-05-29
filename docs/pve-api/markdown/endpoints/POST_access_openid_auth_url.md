# POST /access/openid/auth-url

Get the OpenId Authorization Url for the specified realm.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| realm | string | yes | Authentication domain ID |
| redirect-url | string | yes | Redirection Url. The client should set this to the used server url (location.origin). |

## Returns

```json
{
  "description": "Redirection URL.",
  "type": "string"
}
```

## Permissions

```json
{
  "user": "world"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get the OpenId Authorization Url for the specified realm.",
  "method": "POST",
  "name": "auth_url",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "realm": {
        "description": "Authentication domain ID",
        "format": "pve-realm",
        "maxLength": 32,
        "type": "string",
        "typetext": "<string>"
      },
      "redirect-url": {
        "description": "Redirection Url. The client should set this to the used server url (location.origin).",
        "maxLength": 255,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "user": "world"
  },
  "protected": 1,
  "returns": {
    "description": "Redirection URL.",
    "type": "string"
  }
}
```
