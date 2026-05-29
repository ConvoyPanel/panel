# GET /nodes/{node}/storage

Get status for all datastores.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| content | string | no | Only list stores which support this content type. |
| enabled | boolean | no | Only list stores which are enabled (not disabled in config). |
| format | boolean | no | Include information about formats |
| storage | string | no | Only list status for  specified storage |
| target | string | no | If target is different to 'node', we only lists shared storages which content is accessible on this 'node' and the specified 'target' node. |

## Returns

```json
{
  "items": {
    "properties": {
      "active": {
        "description": "Set when storage is accessible.",
        "optional": 1,
        "type": "boolean"
      },
      "avail": {
        "description": "Available storage space in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "content": {
        "description": "Allowed storage content types.",
        "format": "pve-storage-content-list",
        "type": "string"
      },
      "enabled": {
        "description": "Set when storage is enabled (not disabled).",
        "optional": 1,
        "type": "boolean"
      },
      "formats": {
        "description": "Lists the supported and default format. Use 'formats' instead. Only included if 'format' parameter is set.",
        "optional": 1,
        "properties": {
          "default": {
            "description": "The default format of the storage.",
            "enum": [
              "qcow2",
              "raw",
              "subvol",
              "vmdk"
            ],
            "type": "string"
          },
          "supported": {
            "description": "The list of supported formats",
            "items": {
              "enum": [
                "qcow2",
                "raw",
                "subvol",
                "vmdk"
              ],
              "type": "string"
            },
            "type": "array"
          }
        },
        "type": "object"
      },
      "select_existing": {
        "description": "Instead of creating new volumes, one must select one that is already existing. Only included if 'format' parameter is set.",
        "optional": 1,
        "type": "boolean"
      },
      "shared": {
        "description": "Shared flag from storage configuration.",
        "optional": 1,
        "type": "boolean"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string"
      },
      "total": {
        "description": "Total storage space in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "type": {
        "description": "Storage type.",
        "type": "string"
      },
      "used": {
        "description": "Used storage space in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "used_fraction": {
        "description": "Used fraction (used/total).",
        "optional": 1,
        "renderer": "fraction_as_percentage",
        "type": "number"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{storage}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'Datastore.Audit' or 'Datastore.AllocateSpace' permissions on '/storage/<storage>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get status for all datastores.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "content": {
        "description": "Only list stores which support this content type.",
        "format": "pve-storage-content-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "enabled": {
        "default": 0,
        "description": "Only list stores which are enabled (not disabled in config).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "format": {
        "default": 0,
        "description": "Include information about formats",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "Only list status for  specified storage",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string",
        "typetext": "<storage ID>"
      },
      "target": {
        "description": "If target is different to 'node', we only lists shared storages which content is accessible on this 'node' and the specified 'target' node.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'Datastore.Audit' or 'Datastore.AllocateSpace' permissions on '/storage/<storage>'",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "active": {
          "description": "Set when storage is accessible.",
          "optional": 1,
          "type": "boolean"
        },
        "avail": {
          "description": "Available storage space in bytes.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "content": {
          "description": "Allowed storage content types.",
          "format": "pve-storage-content-list",
          "type": "string"
        },
        "enabled": {
          "description": "Set when storage is enabled (not disabled).",
          "optional": 1,
          "type": "boolean"
        },
        "formats": {
          "description": "Lists the supported and default format. Use 'formats' instead. Only included if 'format' parameter is set.",
          "optional": 1,
          "properties": {
            "default": {
              "description": "The default format of the storage.",
              "enum": [
                "qcow2",
                "raw",
                "subvol",
                "vmdk"
              ],
              "type": "string"
            },
            "supported": {
              "description": "The list of supported formats",
              "items": {
                "enum": [
                  "qcow2",
                  "raw",
                  "subvol",
                  "vmdk"
                ],
                "type": "string"
              },
              "type": "array"
            }
          },
          "type": "object"
        },
        "select_existing": {
          "description": "Instead of creating new volumes, one must select one that is already existing. Only included if 'format' parameter is set.",
          "optional": 1,
          "type": "boolean"
        },
        "shared": {
          "description": "Shared flag from storage configuration.",
          "optional": 1,
          "type": "boolean"
        },
        "storage": {
          "description": "The storage identifier.",
          "format": "pve-storage-id",
          "format_description": "storage ID",
          "type": "string"
        },
        "total": {
          "description": "Total storage space in bytes.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "type": {
          "description": "Storage type.",
          "type": "string"
        },
        "used": {
          "description": "Used storage space in bytes.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "used_fraction": {
          "description": "Used fraction (used/total).",
          "optional": 1,
          "renderer": "fraction_as_percentage",
          "type": "number"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{storage}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
