# DELETE /nodes/{node}/ceph/osd/{osdid}

Destroy OSD

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| osdid | integer | yes | OSD ID |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cleanup | boolean | no | If set, also destroy the underlying logical volumes via 'ceph-volume lvm zap --destroy', remove the volume group's physical volume with pvremove, and wipe any journal/block.db/block.wal partitions left over from filestore OSDs. Without this flag the LVs and partitions are left intact for inspection. |

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
  "description": "Destroy OSD",
  "method": "DELETE",
  "name": "destroyosd",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cleanup": {
        "default": 0,
        "description": "If set, also destroy the underlying logical volumes via 'ceph-volume lvm zap --destroy', remove the volume group's physical volume with pvremove, and wipe any journal/block.db/block.wal partitions left over from filestore OSDs. Without this flag the LVs and partitions are left intact for inspection.",
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
      "osdid": {
        "description": "OSD ID",
        "type": "integer",
        "typetext": "<integer>"
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
