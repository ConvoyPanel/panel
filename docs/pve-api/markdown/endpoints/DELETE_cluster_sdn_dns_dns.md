# DELETE /cluster/sdn/dns/{dns}

Delete sdn dns object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| dns | string | yes | The SDN dns object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| lock-token | string | no | the token for unlocking the global SDN configuration |

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
    "/sdn/dns",
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
  "description": "Delete sdn dns object configuration.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "dns": {
        "description": "The SDN dns object identifier.",
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/dns",
      [
        "SDN.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
