# GET /cluster/sdn/dns/{dns}

Read sdn dns configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| dns | string | yes | The SDN dns object identifier. |

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
    "/sdn/dns/{dns}",
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
  "description": "Read sdn dns configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "dns": {
        "description": "The SDN dns object identifier.",
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/dns/{dns}",
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
