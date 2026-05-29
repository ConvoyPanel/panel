# GET /nodes/{node}/ceph/osd/{osdid}/lv-info

Get OSD volume details

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| osdid | integer | yes | OSD ID |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | OSD device type |

## Returns

```json
{
  "properties": {
    "creation_time": {
      "description": "Creation time as reported by `lvs`.",
      "type": "string"
    },
    "lv_name": {
      "description": "Name of the logical volume (LV).",
      "type": "string"
    },
    "lv_path": {
      "description": "Path to the logical volume (LV).",
      "type": "string"
    },
    "lv_size": {
      "description": "Size of the logical volume (LV).",
      "type": "integer"
    },
    "lv_uuid": {
      "description": "UUID of the logical volume (LV).",
      "type": "string"
    },
    "vg_name": {
      "description": "Name of the volume group (VG).",
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get OSD volume details",
  "method": "GET",
  "name": "osdvolume",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
      },
      "type": {
        "default": "block",
        "description": "OSD device type",
        "enum": [
          "block",
          "db",
          "wal"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "creation_time": {
        "description": "Creation time as reported by `lvs`.",
        "type": "string"
      },
      "lv_name": {
        "description": "Name of the logical volume (LV).",
        "type": "string"
      },
      "lv_path": {
        "description": "Path to the logical volume (LV).",
        "type": "string"
      },
      "lv_size": {
        "description": "Size of the logical volume (LV).",
        "type": "integer"
      },
      "lv_uuid": {
        "description": "UUID of the logical volume (LV).",
        "type": "string"
      },
      "vg_name": {
        "description": "Name of the volume group (VG).",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
