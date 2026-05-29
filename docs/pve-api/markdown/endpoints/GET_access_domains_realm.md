# GET /access/domains/{realm}

Get auth server configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| realm | string | yes | Authentication domain ID |

## Request parameters

None.

## Returns

```json
{}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/access/realm",
    [
      "Realm.Allocate",
      "Sys.Audit"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get auth server configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "realm": {
        "description": "Authentication domain ID",
        "format": "pve-realm",
        "maxLength": 32,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/access/realm",
      [
        "Realm.Allocate",
        "Sys.Audit"
      ],
      "any",
      1
    ]
  },
  "returns": {}
}
```
