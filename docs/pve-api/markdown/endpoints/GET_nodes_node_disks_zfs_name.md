# GET /nodes/{node}/disks/zfs/{name}

Get details about a zpool.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The storage identifier. |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "action": {
      "description": "Information about the recommended action to fix the state.",
      "optional": 1,
      "type": "string"
    },
    "children": {
      "description": "The pool configuration information, including the vdevs for each section (e.g. spares, cache), may be nested.",
      "items": {
        "properties": {
          "cksum": {
            "optional": 1,
            "type": "number"
          },
          "msg": {
            "description": "An optional message about the vdev.",
            "type": "string"
          },
          "name": {
            "description": "The name of the vdev or section.",
            "type": "string"
          },
          "read": {
            "optional": 1,
            "type": "number"
          },
          "state": {
            "description": "The state of the vdev.",
            "optional": 1,
            "type": "string"
          },
          "write": {
            "optional": 1,
            "type": "number"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "errors": {
      "description": "Information about the errors on the zpool.",
      "type": "string"
    },
    "name": {
      "description": "The name of the zpool.",
      "type": "string"
    },
    "scan": {
      "description": "Information about the last/current scrub.",
      "optional": 1,
      "type": "string"
    },
    "state": {
      "description": "The state of the zpool.",
      "type": "string"
    },
    "status": {
      "description": "Information about the state of the zpool.",
      "optional": 1,
      "type": "string"
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
  "description": "Get details about a zpool.",
  "method": "GET",
  "name": "detail",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
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
      "action": {
        "description": "Information about the recommended action to fix the state.",
        "optional": 1,
        "type": "string"
      },
      "children": {
        "description": "The pool configuration information, including the vdevs for each section (e.g. spares, cache), may be nested.",
        "items": {
          "properties": {
            "cksum": {
              "optional": 1,
              "type": "number"
            },
            "msg": {
              "description": "An optional message about the vdev.",
              "type": "string"
            },
            "name": {
              "description": "The name of the vdev or section.",
              "type": "string"
            },
            "read": {
              "optional": 1,
              "type": "number"
            },
            "state": {
              "description": "The state of the vdev.",
              "optional": 1,
              "type": "string"
            },
            "write": {
              "optional": 1,
              "type": "number"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "errors": {
        "description": "Information about the errors on the zpool.",
        "type": "string"
      },
      "name": {
        "description": "The name of the zpool.",
        "type": "string"
      },
      "scan": {
        "description": "Information about the last/current scrub.",
        "optional": 1,
        "type": "string"
      },
      "state": {
        "description": "The state of the zpool.",
        "type": "string"
      },
      "status": {
        "description": "Information about the state of the zpool.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
