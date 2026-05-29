# GET /cluster/sdn/vnets/{vnet}/subnets/{subnet}

Read sdn subnet configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| subnet | string | yes | The SDN subnet object identifier. |
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |

## Returns

```json
{
  "type": "object"
}
```

## Permissions

```json
{
  "description": "Require 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read sdn subnet configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "pending": {
        "description": "Display pending config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "running": {
        "description": "Display running config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "description": "Require 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "returns": {
    "type": "object"
  }
}
```
