# PUT /nodes/{node}/storage/{storage}/content/{volume}

Update volume attributes

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| volume | string | yes | Volume identifier |
| storage | string | no | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| notes | string | no | The new notes. |
| protected | boolean | no | Protection status. Currently only supported for backups. |

## Returns

```json
{
  "type": "null"
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
  "description": "Update volume attributes",
  "method": "PUT",
  "name": "updateattributes",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "notes": {
        "description": "The new notes.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "protected": {
        "description": "Protection status. Currently only supported for backups.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "type": "null"
  }
}
```
