# DELETE /cluster/sdn/vnets/{vnet}/firewall/rules/{pos}

Delete rule.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |
| pos | integer | no | Update rule at position <pos>. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "description": "Needs SDN.Allocate permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete rule.",
  "method": "DELETE",
  "name": "delete_rule",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "pos": {
        "description": "Update rule at position <pos>.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "vnet": {
        "description": "The SDN vnet object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Needs SDN.Allocate permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "protected": 1,
  "proxyto": null,
  "returns": {
    "type": "null"
  }
}
```
