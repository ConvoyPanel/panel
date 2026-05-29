# GET /nodes/{node}/vzdump/extractconfig

Extract configuration from vzdump backup archive.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| volume | string | yes | Volume identifier |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "description": "The user needs 'VM.Backup' permissions on the backed up guest ID, and 'Datastore.AllocateSpace' on the backup storage.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Extract configuration from vzdump backup archive.",
  "method": "GET",
  "name": "extractconfig",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "volume": {
        "description": "Volume identifier",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "The user needs 'VM.Backup' permissions on the backed up guest ID, and 'Datastore.AllocateSpace' on the backup storage.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
