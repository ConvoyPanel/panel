# DELETE /nodes/{node}/storage/{storage}/content/{volume}

Delete volume

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| volume | string | yes | Volume identifier |
| storage | string | no | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delay | integer | no | Time to wait for the task to finish. We return 'null' if the task finish within that time. |

## Returns

```json
{
  "optional": 1,
  "type": "string"
}
```

## Permissions

```json
{
  "description": "You need 'Datastore.Allocate' privilege on the storage (or 'Datastore.AllocateSpace' for backup volumes if you have VM.Backup privilege on the VM).",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete volume",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delay": {
        "description": "Time to wait for the task to finish. We return 'null' if the task finish within that time.",
        "maximum": 30,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 30)"
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
    "description": "You need 'Datastore.Allocate' privilege on the storage (or 'Datastore.AllocateSpace' for backup volumes if you have VM.Backup privilege on the VM).",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "optional": 1,
    "type": "string"
  }
}
```
