# DELETE /cluster/sdn/controllers/{controller}

Delete sdn controller object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| controller | string | yes | The SDN controller object identifier. |

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
    "/sdn/controllers",
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
  "description": "Delete sdn controller object configuration.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "controller": {
        "description": "The SDN controller object identifier.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
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
      "/sdn/controllers",
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
