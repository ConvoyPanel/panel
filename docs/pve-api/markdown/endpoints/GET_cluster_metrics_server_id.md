# GET /cluster/metrics/server/{id}

Read metric server configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read metric server configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "type": "object"
  }
}
```
