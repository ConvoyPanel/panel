# GET /nodes/{node}/storage/{storage}/content/{volume}

Get volume attributes

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| volume | string | yes | Volume identifier |
| storage | string | no | The storage identifier. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "format": {
      "description": "Format identifier ('raw', 'qcow2', 'subvol', 'iso', 'tgz' ...)",
      "type": "string"
    },
    "notes": {
      "description": "Optional notes.",
      "optional": 1,
      "type": "string"
    },
    "path": {
      "description": "The Path",
      "type": "string"
    },
    "protected": {
      "description": "Protection status. Currently only supported for backups.",
      "optional": 1,
      "type": "boolean"
    },
    "size": {
      "description": "Volume size in bytes.",
      "renderer": "bytes",
      "type": "integer"
    },
    "used": {
      "description": "Used space. Please note that most storage plugins do not report anything useful here.",
      "renderer": "bytes",
      "type": "integer"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "You need read access for the volume.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get volume attributes",
  "method": "GET",
  "name": "info",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string",
        "typetext": "<storage ID>"
      },
      "volume": {
        "description": "Volume identifier",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "You need read access for the volume.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "format": {
        "description": "Format identifier ('raw', 'qcow2', 'subvol', 'iso', 'tgz' ...)",
        "type": "string"
      },
      "notes": {
        "description": "Optional notes.",
        "optional": 1,
        "type": "string"
      },
      "path": {
        "description": "The Path",
        "type": "string"
      },
      "protected": {
        "description": "Protection status. Currently only supported for backups.",
        "optional": 1,
        "type": "boolean"
      },
      "size": {
        "description": "Volume size in bytes.",
        "renderer": "bytes",
        "type": "integer"
      },
      "used": {
        "description": "Used space. Please note that most storage plugins do not report anything useful here.",
        "renderer": "bytes",
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
