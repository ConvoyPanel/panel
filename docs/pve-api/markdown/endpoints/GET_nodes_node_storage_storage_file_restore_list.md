# GET /nodes/{node}/storage/{storage}/file-restore/list

List files and directories for single file restore under the given path.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| filepath | string | yes | base64-path to the directory or file being listed, or "/". |
| volume | string | yes | Backup volume ID or name. Currently only PBS snapshots are supported. |

## Returns

```json
{
  "items": {
    "properties": {
      "filepath": {
        "description": "base64 path of the current entry",
        "type": "string"
      },
      "leaf": {
        "description": "If this entry is a leaf in the directory graph.",
        "type": "boolean"
      },
      "mtime": {
        "description": "Entry last-modified time (unix timestamp).",
        "optional": 1,
        "type": "integer"
      },
      "size": {
        "description": "Entry file size.",
        "optional": 1,
        "type": "integer"
      },
      "text": {
        "description": "Entry display text.",
        "type": "string"
      },
      "type": {
        "description": "Entry type.",
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
  "description": "You need read access for the volume.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List files and directories for single file restore under the given path.",
  "method": "GET",
  "name": "list",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "filepath": {
        "description": "base64-path to the directory or file being listed, or \"/\".",
        "type": "string",
        "typetext": "<string>"
      },
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
        "type": "string",
        "typetext": "<storage ID>"
      },
      "volume": {
        "description": "Backup volume ID or name. Currently only PBS snapshots are supported.",
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
    "items": {
      "properties": {
        "filepath": {
          "description": "base64 path of the current entry",
          "type": "string"
        },
        "leaf": {
          "description": "If this entry is a leaf in the directory graph.",
          "type": "boolean"
        },
        "mtime": {
          "description": "Entry last-modified time (unix timestamp).",
          "optional": 1,
          "type": "integer"
        },
        "size": {
          "description": "Entry file size.",
          "optional": 1,
          "type": "integer"
        },
        "text": {
          "description": "Entry display text.",
          "type": "string"
        },
        "type": {
          "description": "Entry type.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
