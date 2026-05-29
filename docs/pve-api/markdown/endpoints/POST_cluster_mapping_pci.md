# POST /cluster/mapping/pci

Create a new hardware mapping.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the logical PCI mapping. |
| map | array | yes | A list of maps for the cluster nodes. |
| description | string | no | Description of the logical PCI device. |
| live-migration-capable | boolean | no | Marks the device(s) as being able to be live-migrated (Experimental). This needs hardware and driver support to work. |
| mdev | boolean | no | Marks the device(s) as being capable of providing mediated devices. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/mapping/pci",
    [
      "Mapping.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a new hardware mapping.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "description": {
        "description": "Description of the logical PCI device.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "id": {
        "description": "The ID of the logical PCI mapping.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "live-migration-capable": {
        "default": 0,
        "description": "Marks the device(s) as being able to be live-migrated (Experimental). This needs hardware and driver support to work.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "map": {
        "description": "A list of maps for the cluster nodes.",
        "items": {
          "format": {
            "description": {
              "description": "Description of the node specific device.",
              "maxLength": 4096,
              "optional": 1,
              "type": "string"
            },
            "id": {
              "description": "The vendor and device ID that is expected. Used for detecting hardware changes",
              "pattern": "(?^:^[0-9A-Fa-f]{4}:[0-9A-Fa-f]{4}$)",
              "type": "string"
            },
            "iommugroup": {
              "description": "The IOMMU group in which the device is to be expected in. Used for detecting hardware changes.",
              "optional": 1,
              "type": "integer"
            },
            "node": {
              "description": "The cluster node name.",
              "format": "pve-node",
              "type": "string"
            },
            "path": {
              "description": "The path to the device. If the function is omitted, the whole device is mapped. In that case use the attributes of the first device. You can give multiple paths as a semicolon separated list, the first available will then be chosen on guest start.",
              "pattern": "(?:[a-f0-9]{4,}:[a-f0-9]{2}:[a-f0-9]{2}(?:.[a-f0-9])?;)*[a-f0-9]{4,}:[a-f0-9]{2}:[a-f0-9]{2}(?:.[a-f0-9])?",
              "type": "string"
            },
            "subsystem-id": {
              "description": "The subsystem vendor and device ID that is expected. Used for detecting hardware changes.",
              "optional": 1,
              "pattern": "(?^:^[0-9A-Fa-f]{4}:[0-9A-Fa-f]{4}$)",
              "type": "string"
            }
          },
          "type": "string"
        },
        "optional": 0,
        "type": "array",
        "typetext": "<array>"
      },
      "mdev": {
        "default": 0,
        "description": "Marks the device(s) as being capable of providing mediated devices.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/pci",
      [
        "Mapping.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
