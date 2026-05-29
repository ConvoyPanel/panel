# PUT /nodes/{node}/disks/wipedisk

Wipe a disk or partition.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| disk | string | yes | Block device name |

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
  "description": "Wipe a disk or partition.",
  "method": "PUT",
  "name": "wipe_disk",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "disk": {
        "description": "Block device name",
        "pattern": "^/dev/[a-zA-Z0-9\\/]+$",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
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
