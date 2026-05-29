# GET /cluster/sdn/vnets/{vnet}/subnets

SDN subnets index.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |

## Returns

```json
{
  "items": {
    "properties": {},
    "type": "object"
  },
  "links": [
    {
      "href": "{subnet}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "SDN subnets index.",
  "method": "GET",
  "name": "index",
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
    "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "links": [
      {
        "href": "{subnet}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
