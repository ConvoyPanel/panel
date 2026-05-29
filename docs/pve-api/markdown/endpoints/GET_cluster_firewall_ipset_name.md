# GET /cluster/firewall/ipset/{name}

List IPSet content

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | IP set name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "cidr": {
        "type": "string"
      },
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 0,
        "type": "string"
      },
      "nomatch": {
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{cidr}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List IPSet content",
  "method": "GET",
  "name": "get_ipset",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "IP set name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "cidr": {
          "type": "string"
        },
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "digest": {
          "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
          "maxLength": 64,
          "optional": 0,
          "type": "string"
        },
        "nomatch": {
          "optional": 1,
          "type": "boolean"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{cidr}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
