# POST /nodes/{node}/disks/zfs

Create a ZFS pool.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| devices | string | yes | The block devices you want to create the zpool on. |
| name | string | yes | The storage identifier. |
| raidlevel | string | yes | The RAID level to use. |
| add_storage | boolean | no | Configure storage using the zpool. |
| ashift | integer | no | Pool sector size exponent. |
| compression | string | no | The compression algorithm to use. |
| draid-config | string | no |  |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Modify"
    ]
  ],
  "description": "Requires additionally 'Datastore.Allocate' on /storage when setting 'add_storage'"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a ZFS pool.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "add_storage": {
        "default": 0,
        "description": "Configure storage using the zpool.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "ashift": {
        "default": 12,
        "description": "Pool sector size exponent.",
        "maximum": 16,
        "minimum": 9,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (9 - 16)"
      },
      "compression": {
        "default": "on",
        "description": "The compression algorithm to use.",
        "enum": [
          "on",
          "off",
          "gzip",
          "lz4",
          "lzjb",
          "zle",
          "zstd"
        ],
        "optional": 1,
        "type": "string"
      },
      "devices": {
        "description": "The block devices you want to create the zpool on.",
        "format": "string-list",
        "type": "string",
        "typetext": "<string>"
      },
      "draid-config": {
        "format": {
          "data": {
            "description": "The number of data devices per redundancy group. (dRAID)",
            "minimum": 1,
            "type": "integer"
          },
          "spares": {
            "description": "Number of dRAID spares.",
            "minimum": 0,
            "type": "integer"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "data=<integer> ,spares=<integer>"
      },
      "name": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "raidlevel": {
        "description": "The RAID level to use.",
        "enum": [
          "single",
          "mirror",
          "raid10",
          "raidz",
          "raidz2",
          "raidz3",
          "draid",
          "draid2",
          "draid3"
        ],
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ],
    "description": "Requires additionally 'Datastore.Allocate' on /storage when setting 'add_storage'"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
