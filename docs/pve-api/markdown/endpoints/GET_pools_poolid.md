# GET /pools/{poolid}

Get pool configuration (deprecated, no support for nested pools, use 'GET /pools/?poolid={poolid}').

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| poolid | string | yes |  |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no |  |

## Returns

```json
{
  "additionalProperties": 0,
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
      "type": "array"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/pool/{poolid}",
    [
      "Pool.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get pool configuration (deprecated, no support for nested pools, use 'GET /pools/?poolid={poolid}').",
  "method": "GET",
  "name": "read_pool",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "poolid": {
        "format": "pve-poolid",
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
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/pool/{poolid}",
      [
        "Pool.Audit"
      ]
    ]
  },
  "returns": {
    "additionalProperties": 0,
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
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
