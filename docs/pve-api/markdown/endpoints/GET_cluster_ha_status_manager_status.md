# GET /cluster/ha/status/manager_status

Get full HA manager status, including LRM status.

## Path parameters

None.

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
  "description": "Get full HA manager status, including LRM status.",
  "method": "GET",
  "name": "manager_status",
  "parameters": {
    "additionalProperties": 0
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
