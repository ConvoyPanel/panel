# GET /cluster/backup/{id}/included_volumes

Returns included guests and the backup status of their disks. Optimized to be used in ExtJS tree views.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The job ID. |

## Request parameters

None.

## Returns

```json
{
  "description": "Root node of the tree object. Children represent guests, grandchildren represent volumes of that guest.",
  "properties": {
    "children": {
      "items": {
        "properties": {
          "children": {
            "description": "The volumes of the guest with the information if they will be included in backups.",
            "items": {
              "properties": {
                "id": {
                  "description": "Configuration key of the volume.",
                  "type": "string"
                },
                "included": {
                  "description": "Whether the volume is included in the backup or not.",
                  "type": "boolean"
                },
                "name": {
                  "description": "Name of the volume.",
                  "type": "string"
                },
                "reason": {
                  "description": "The reason why the volume is included (or excluded).",
                  "type": "string"
                }
              },
              "type": "object"
            },
            "optional": 1,
            "type": "array"
          },
          "id": {
            "description": "VMID of the guest.",
            "type": "integer"
          },
          "name": {
            "description": "Name of the guest",
            "optional": 1,
            "type": "string"
          },
          "type": {
            "description": "Type of the guest, VM, CT or unknown for removed but not purged guests.",
            "enum": [
              "qemu",
              "lxc",
              "unknown"
            ],
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
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
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Returns included guests and the backup status of their disks. Optimized to be used in ExtJS tree views.",
  "method": "GET",
  "name": "get_volume_backup_included",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "The job ID.",
        "maxLength": 50,
        "pattern": "\\S+",
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
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "description": "Root node of the tree object. Children represent guests, grandchildren represent volumes of that guest.",
    "properties": {
      "children": {
        "items": {
          "properties": {
            "children": {
              "description": "The volumes of the guest with the information if they will be included in backups.",
              "items": {
                "properties": {
                  "id": {
                    "description": "Configuration key of the volume.",
                    "type": "string"
                  },
                  "included": {
                    "description": "Whether the volume is included in the backup or not.",
                    "type": "boolean"
                  },
                  "name": {
                    "description": "Name of the volume.",
                    "type": "string"
                  },
                  "reason": {
                    "description": "The reason why the volume is included (or excluded).",
                    "type": "string"
                  }
                },
                "type": "object"
              },
              "optional": 1,
              "type": "array"
            },
            "id": {
              "description": "VMID of the guest.",
              "type": "integer"
            },
            "name": {
              "description": "Name of the guest",
              "optional": 1,
              "type": "string"
            },
            "type": {
              "description": "Type of the guest, VM, CT or unknown for removed but not purged guests.",
              "enum": [
                "qemu",
                "lxc",
                "unknown"
              ],
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
