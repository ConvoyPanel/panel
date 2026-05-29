# DELETE /cluster/ha/groups/{group}

Delete ha group configuration. (deprecated in favor of HA rules)

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| group | string | yes | The HA group identifier. |

## Request parameters

None.

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete ha group configuration. (deprecated in favor of HA rules)",
  "method": "DELETE",
  "name": "delete",
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
        "Sys.Console"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
