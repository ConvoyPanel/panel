# DELETE /cluster/ha/rules/{rule}

Delete HA rule.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| rule | string | yes | HA rule identifier. |

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
  "description": "Delete HA rule.",
  "method": "DELETE",
  "name": "delete_rule",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "rule": {
        "description": "HA rule identifier.",
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
