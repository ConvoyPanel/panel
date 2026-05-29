# GET /cluster/sdn/dns

SDN dns index.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list sdn dns of specific type |

## Returns

```json
{
  "items": {
    "properties": {
      "dns": {
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
      "href": "{dns}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/dns/<dns>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "SDN dns index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Only list sdn dns of specific type",
        "enum": [
          "powerdns"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/dns/<dns>'",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "dns": {
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
        "href": "{dns}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
