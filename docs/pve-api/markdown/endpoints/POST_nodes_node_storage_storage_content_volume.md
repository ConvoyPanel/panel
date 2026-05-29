# POST /nodes/{node}/storage/{storage}/content/{volume}

Copy a volume. This is experimental code - do not use.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| volume | string | yes | Source volume identifier |
| storage | string | no | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target | string | yes | Target volume identifier |
| target_node | string | no | Target node. Default is local node. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Copy a volume. This is experimental code - do not use.",
  "method": "POST",
  "name": "copy",
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
      "target": {
        "description": "Target volume identifier",
        "type": "string",
        "typetext": "<string>"
      },
      "target_node": {
        "description": "Target node. Default is local node.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "volume": {
        "description": "Source volume identifier",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
