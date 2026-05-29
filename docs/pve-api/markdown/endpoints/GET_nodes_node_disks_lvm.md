# GET /nodes/{node}/disks/lvm

List LVM Volume Groups

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "children": {
      "items": {
        "properties": {
          "children": {
            "description": "The underlying physical volumes",
            "items": {
              "properties": {
                "free": {
                  "description": "The free bytes in the physical volume",
                  "type": "integer"
                },
                "leaf": {
                  "type": "boolean"
                },
                "name": {
                  "description": "The name of the physical volume",
                  "type": "string"
                },
                "size": {
                  "description": "The size of the physical volume in bytes",
                  "type": "integer"
                }
              },
              "type": "object"
            },
            "optional": 1,
            "type": "array"
          },
          "free": {
            "description": "The free bytes in the volume group",
            "type": "integer"
          },
          "leaf": {
            "type": "boolean"
          },
          "name": {
            "description": "The name of the volume group",
            "type": "string"
          },
          "size": {
            "description": "The size of the volume group in bytes",
            "type": "integer"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "leaf": {
      "type": "boolean"
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
  "description": "List LVM Volume Groups",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "children": {
        "items": {
          "properties": {
            "children": {
              "description": "The underlying physical volumes",
              "items": {
                "properties": {
                  "free": {
                    "description": "The free bytes in the physical volume",
                    "type": "integer"
                  },
                  "leaf": {
                    "type": "boolean"
                  },
                  "name": {
                    "description": "The name of the physical volume",
                    "type": "string"
                  },
                  "size": {
                    "description": "The size of the physical volume in bytes",
                    "type": "integer"
                  }
                },
                "type": "object"
              },
              "optional": 1,
              "type": "array"
            },
            "free": {
              "description": "The free bytes in the volume group",
              "type": "integer"
            },
            "leaf": {
              "type": "boolean"
            },
            "name": {
              "description": "The name of the volume group",
              "type": "string"
            },
            "size": {
              "description": "The size of the volume group in bytes",
              "type": "integer"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "leaf": {
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
