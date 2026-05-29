# POST /access/openid/login

Verify OpenID authorization code and create a ticket.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| code | string | yes | OpenId authorization code. |
| redirect-url | string | yes | Redirection Url. The client should set this to the used server url (location.origin). |
| state | string | yes | OpenId state. |

## Returns

```json
{
  "properties": {
    "CSRFPreventionToken": {
      "type": "string"
    },
    "cap": {
      "type": "object"
    },
    "clustername": {
      "optional": 1,
      "type": "string"
    },
    "ticket": {
      "type": "string"
    },
    "username": {
      "type": "string"
    }
  }
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
  "description": " Verify OpenID authorization code and create a ticket.",
  "method": "POST",
  "name": "login",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "code": {
        "description": "OpenId authorization code.",
        "maxLength": 4096,
        "type": "string",
        "typetext": "<string>"
      },
      "redirect-url": {
        "description": "Redirection Url. The client should set this to the used server url (location.origin).",
        "maxLength": 255,
        "type": "string",
        "typetext": "<string>"
      },
      "state": {
        "description": "OpenId state.",
        "maxLength": 1024,
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
    "properties": {
      "CSRFPreventionToken": {
        "type": "string"
      },
      "cap": {
        "type": "object"
      },
      "clustername": {
        "optional": 1,
        "type": "string"
      },
      "ticket": {
        "type": "string"
      },
      "username": {
        "type": "string"
      }
    }
  }
}
```
