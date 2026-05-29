# GET /nodes/{node}/storage/{storage}/file-restore/download

Extract a file or directory (as zip archive) from a PBS backup.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| filepath | string | yes | base64-path to the directory or file to download. |
| volume | string | yes | Backup volume ID or name. Currently only PBS snapshots are supported. |
| tar | boolean | no | Download dirs as 'tar.zst' instead of 'zip'. |

## Returns

```json
{
  "type": "any"
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
  "description": "Extract a file or directory (as zip archive) from a PBS backup.",
  "download_allowed": 1,
  "method": "GET",
  "name": "download",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "filepath": {
        "description": "base64-path to the directory or file to download.",
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
      "tar": {
        "default": 0,
        "description": "Download dirs as 'tar.zst' instead of 'zip'.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "type": "any"
  }
}
```
