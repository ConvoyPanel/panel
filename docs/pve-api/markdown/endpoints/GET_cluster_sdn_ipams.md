# GET /cluster/sdn/ipams

SDN ipams index.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list sdn ipams of specific type |

## Returns

```json
{
  "items": {
    "properties": {
      "ipam": {
        "type": "string"
      },
      "type": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{ipam}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/ipams/<ipam>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "SDN ipams index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Only list sdn ipams of specific type",
        "enum": [
          "netbox",
          "phpipam",
          "pve"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/ipams/<ipam>'",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "ipam": {
          "type": "string"
        },
        "type": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{ipam}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
