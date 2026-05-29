# GET /access/domains

Authentication domain index.

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
        "description": "A comment. The GUI use this text when you select a domain (Realm) on the login window.",
        "optional": 1,
        "type": "string"
      },
      "realm": {
        "type": "string"
      },
      "tfa": {
        "description": "Two-factor authentication provider.",
        "enum": [
          "yubico",
          "oath"
        ],
        "optional": 1,
        "type": "string"
      },
      "type": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{realm}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Anyone can access that, because we need that list for the login box (before the user is authenticated).",
  "user": "world"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Authentication domain index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "description": "Anyone can access that, because we need that list for the login box (before the user is authenticated).",
    "user": "world"
  },
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "description": "A comment. The GUI use this text when you select a domain (Realm) on the login window.",
          "optional": 1,
          "type": "string"
        },
        "realm": {
          "type": "string"
        },
        "tfa": {
          "description": "Two-factor authentication provider.",
          "enum": [
            "yubico",
            "oath"
          ],
          "optional": 1,
          "type": "string"
        },
        "type": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{realm}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
