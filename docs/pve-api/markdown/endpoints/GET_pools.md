# GET /pools

List pools or get pool configuration.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| poolid | string | no |  |
| type | string | no |  |

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "members": {
        "items": {
          "additionalProperties": 1,
          "properties": {
            "id": {
              "type": "string"
            },
            "node": {
              "type": "string"
            },
            "storage": {
              "optional": 1,
              "type": "string"
            },
            "type": {
              "enum": [
                "qemu",
                "lxc",
                "openvz",
                "storage"
              ],
              "type": "string"
            },
            "vmid": {
              "optional": 1,
              "type": "integer"
            }
          },
          "type": "object"
        },
        "optional": 1,
        "type": "array"
      },
      "poolid": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{poolid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "List all pools where you have Pool.Audit permissions on /pool/<pool>, or the pool specific with {poolid}",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List pools or get pool configuration.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "poolid": {
        "format": "pve-poolid",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "enum": [
          "qemu",
          "lxc",
          "storage"
        ],
        "optional": 1,
        "requires": "poolid",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "List all pools where you have Pool.Audit permissions on /pool/<pool>, or the pool specific with {poolid}",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "members": {
          "items": {
            "additionalProperties": 1,
            "properties": {
              "id": {
                "type": "string"
              },
              "node": {
                "type": "string"
              },
              "storage": {
                "optional": 1,
                "type": "string"
              },
              "type": {
                "enum": [
                  "qemu",
                  "lxc",
                  "openvz",
                  "storage"
                ],
                "type": "string"
              },
              "vmid": {
                "optional": 1,
                "type": "integer"
              }
            },
            "type": "object"
          },
          "optional": 1,
          "type": "array"
        },
        "poolid": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{poolid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
