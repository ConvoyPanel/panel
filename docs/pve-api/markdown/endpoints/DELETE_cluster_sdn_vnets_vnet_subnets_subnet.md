# DELETE /cluster/sdn/vnets/{vnet}/subnets/{subnet}

Delete sdn subnet object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| subnet | string | yes | The SDN subnet object identifier. |
| vnet | string | yes | The SDN vnet object identifier. |

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
  "description": "Require 'SDN.Allocate' permission on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete sdn subnet object configuration.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "subnet": {
        "description": "The SDN subnet object identifier.",
        "format": "pve-sdn-subnet-id",
        "type": "string",
        "typetext": "<string>"
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
    "description": "Require 'SDN.Allocate' permission on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
