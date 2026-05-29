# POST /nodes/{node}/ceph/osd

Create OSD

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| dev | string | yes | Block device name. |
| crush-device-class | string | no | Set the device class of the OSD in crush. |
| db_dev | string | no | Block device name for block.db. |
| db_dev_size | number | no | Size in GiB for block.db. |
| encrypted | boolean | no | Enables encryption of the OSD. |
| osds-per-device | integer | no | OSD services per physical device. Only useful for fast NVMe devices to utilize their performance better. Mutually exclusive with 'db_dev' and 'wal_dev'. |
| wal_dev | string | no | Block device name for block.wal. |
| wal_dev_size | number | no | Size in GiB for block.wal. |

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
  "description": "Create OSD",
  "method": "POST",
  "name": "createosd",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "crush-device-class": {
        "description": "Set the device class of the OSD in crush.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "db_dev": {
        "description": "Block device name for block.db.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "db_dev_size": {
        "description": "Size in GiB for block.db.",
        "minimum": 1,
        "optional": 1,
        "requires": "db_dev",
        "type": "number",
        "typetext": "<number> (1 - N)",
        "verbose_description": "If a block.db is requested but the size is not given, will be automatically selected by: bluestore_block_db_size from the ceph database (osd or global section) or config (osd or global section) in that order. If this is not available, it will be sized 10% of the size of the OSD device. Fails if the available size is not enough."
      },
      "dev": {
        "description": "Block device name.",
        "type": "string",
        "typetext": "<string>"
      },
      "encrypted": {
        "default": 0,
        "description": "Enables encryption of the OSD.",
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
      "osds-per-device": {
        "description": "OSD services per physical device. Only useful for fast NVMe devices to utilize their performance better. Mutually exclusive with 'db_dev' and 'wal_dev'.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      },
      "wal_dev": {
        "description": "Block device name for block.wal.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "wal_dev_size": {
        "description": "Size in GiB for block.wal.",
        "minimum": 0.5,
        "optional": 1,
        "requires": "wal_dev",
        "type": "number",
        "typetext": "<number> (0.5 - N)",
        "verbose_description": "If a block.wal is requested but the size is not given, will be automatically selected by: bluestore_block_wal_size from the ceph database (osd or global section) or config (osd or global section) in that order. If this is not available, it will be sized 1% of the size of the OSD device. Fails if the available size is not enough."
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
