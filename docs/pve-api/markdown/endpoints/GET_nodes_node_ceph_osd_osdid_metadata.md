# GET /nodes/{node}/ceph/osd/{osdid}/metadata

Get OSD details

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| osdid | integer | yes | OSD ID |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "devices": {
      "description": "Array containing data about devices",
      "items": {
        "properties": {
          "dev_node": {
            "description": "Device node",
            "type": "string"
          },
          "device": {
            "description": "Kind of OSD device",
            "enum": [
              "block",
              "db",
              "wal"
            ],
            "type": "string"
          },
          "physical_device": {
            "description": "Underlying physical device(s) used by this OSD device (comma- or space-joined when multiple).",
            "type": "string"
          },
          "size": {
            "description": "Size of the OSD device in bytes.",
            "type": "integer"
          },
          "support_discard": {
            "description": "Whether the underlying physical device supports discard/TRIM.",
            "type": "boolean"
          },
          "type": {
            "description": "Type of device. For example, hdd or ssd",
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "osd": {
      "description": "General information about the OSD",
      "properties": {
        "back_addr": {
          "description": "Address and port used to talk to other OSDs.",
          "type": "string"
        },
        "encrypted": {
          "description": "Whether the OSD is encrypted with LUKS via dm-crypt.",
          "type": "boolean"
        },
        "front_addr": {
          "description": "Address and port used to talk to clients and monitors.",
          "type": "string"
        },
        "hb_back_addr": {
          "description": "Heartbeat address and port for other OSDs.",
          "type": "string"
        },
        "hb_front_addr": {
          "description": "Heartbeat address and port for clients and monitors.",
          "type": "string"
        },
        "hostname": {
          "description": "Name of the host containing the OSD.",
          "type": "string"
        },
        "id": {
          "description": "ID of the OSD.",
          "type": "integer"
        },
        "mem_usage": {
          "description": "Proportional set size (PSS) memory usage of the OSD daemon process in bytes; 0 when the process is not running.",
          "type": "integer"
        },
        "osd_data": {
          "description": "Path to the OSD's data directory.",
          "type": "string"
        },
        "osd_objectstore": {
          "description": "The type of object store used.",
          "type": "string"
        },
        "pid": {
          "description": "OSD process ID; absent if the systemd unit for this OSD is not currently running.",
          "optional": 1,
          "type": "integer"
        },
        "version": {
          "description": "Ceph version of the OSD service.",
          "type": "string"
        }
      },
      "type": "object"
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
  "description": "Get OSD details",
  "method": "GET",
  "name": "osddetails",
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
      "devices": {
        "description": "Array containing data about devices",
        "items": {
          "properties": {
            "dev_node": {
              "description": "Device node",
              "type": "string"
            },
            "device": {
              "description": "Kind of OSD device",
              "enum": [
                "block",
                "db",
                "wal"
              ],
              "type": "string"
            },
            "physical_device": {
              "description": "Underlying physical device(s) used by this OSD device (comma- or space-joined when multiple).",
              "type": "string"
            },
            "size": {
              "description": "Size of the OSD device in bytes.",
              "type": "integer"
            },
            "support_discard": {
              "description": "Whether the underlying physical device supports discard/TRIM.",
              "type": "boolean"
            },
            "type": {
              "description": "Type of device. For example, hdd or ssd",
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "osd": {
        "description": "General information about the OSD",
        "properties": {
          "back_addr": {
            "description": "Address and port used to talk to other OSDs.",
            "type": "string"
          },
          "encrypted": {
            "description": "Whether the OSD is encrypted with LUKS via dm-crypt.",
            "type": "boolean"
          },
          "front_addr": {
            "description": "Address and port used to talk to clients and monitors.",
            "type": "string"
          },
          "hb_back_addr": {
            "description": "Heartbeat address and port for other OSDs.",
            "type": "string"
          },
          "hb_front_addr": {
            "description": "Heartbeat address and port for clients and monitors.",
            "type": "string"
          },
          "hostname": {
            "description": "Name of the host containing the OSD.",
            "type": "string"
          },
          "id": {
            "description": "ID of the OSD.",
            "type": "integer"
          },
          "mem_usage": {
            "description": "Proportional set size (PSS) memory usage of the OSD daemon process in bytes; 0 when the process is not running.",
            "type": "integer"
          },
          "osd_data": {
            "description": "Path to the OSD's data directory.",
            "type": "string"
          },
          "osd_objectstore": {
            "description": "The type of object store used.",
            "type": "string"
          },
          "pid": {
            "description": "OSD process ID; absent if the systemd unit for this OSD is not currently running.",
            "optional": 1,
            "type": "integer"
          },
          "version": {
            "description": "Ceph version of the OSD service.",
            "type": "string"
          }
        },
        "type": "object"
      }
    },
    "type": "object"
  }
}
```
