# GET /cluster/ha/groups/{group}

Read ha group configuration. (deprecated in favor of HA rules)

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| group | string | yes | The HA group identifier. |

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
  "description": "Read ha group configuration. (deprecated in favor of HA rules)",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "group": {
        "description": "The HA group identifier.",
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
  "returns": {}
}
```
