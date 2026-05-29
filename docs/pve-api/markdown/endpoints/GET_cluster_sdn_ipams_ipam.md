# GET /cluster/sdn/ipams/{ipam}

Read sdn ipam configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| ipam | string | yes | The SDN ipam object identifier. |

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
    "/sdn/ipams/{ipam}",
    [
      "SDN.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read sdn ipam configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "ipam": {
        "description": "The SDN ipam object identifier.",
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/ipams/{ipam}",
      [
        "SDN.Allocate"
      ]
    ]
  },
  "returns": {
    "type": "object"
  }
}
```
