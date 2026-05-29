# GET /cluster/firewall/refs

Lists possible IPSet/Alias reference which are allowed in source/dest properties.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list references of specified type. |

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "name": {
        "type": "string"
      },
      "ref": {
        "type": "string"
      },
      "scope": {
        "type": "string"
      },
      "type": {
        "enum": [
          "alias",
          "ipset"
        ],
        "type": "string"
      }
    },
    "type": "object"
  },
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
  "description": "Lists possible IPSet/Alias reference which are allowed in source/dest properties.",
  "method": "GET",
  "name": "refs",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Only list references of specified type.",
        "enum": [
          "alias",
          "ipset"
        ],
        "optional": 1,
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
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "name": {
          "type": "string"
        },
        "ref": {
          "type": "string"
        },
        "scope": {
          "type": "string"
        },
        "type": {
          "enum": [
            "alias",
            "ipset"
          ],
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
