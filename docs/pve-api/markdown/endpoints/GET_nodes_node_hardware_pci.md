# GET /nodes/{node}/hardware/pci

List local PCI devices.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pci-class-blacklist | string | no | A list of blacklisted PCI classes, which will not be returned. Following are filtered by default: Memory Controller (05), Bridge (06) and Processor (0b). |
| verbose | boolean | no | If disabled, does only print the PCI IDs. Otherwise, additional information like vendor and device will be returned. |

## Returns

```json
{
  "items": {
    "properties": {
      "class": {
        "description": "The PCI Class of the device.",
        "type": "string"
      },
      "device": {
        "description": "The Device ID.",
        "type": "string"
      },
      "device_name": {
        "optional": 1,
        "type": "string"
      },
      "id": {
        "description": "The PCI ID.",
        "type": "string"
      },
      "iommugroup": {
        "description": "The IOMMU group in which the device is in. If no IOMMU group is detected, it is set to -1.",
        "type": "integer"
      },
      "mdev": {
        "description": "If set, marks that the device is capable of creating mediated devices.",
        "optional": 1,
        "type": "boolean"
      },
      "subsystem_device": {
        "description": "The Subsystem Device ID.",
        "optional": 1,
        "type": "string"
      },
      "subsystem_device_name": {
        "optional": 1,
        "type": "string"
      },
      "subsystem_vendor": {
        "description": "The Subsystem Vendor ID.",
        "optional": 1,
        "type": "string"
      },
      "subsystem_vendor_name": {
        "optional": 1,
        "type": "string"
      },
      "vendor": {
        "description": "The Vendor ID.",
        "type": "string"
      },
      "vendor_name": {
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{id}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit",
      "Sys.Modify"
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
  "description": "List local PCI devices.",
  "method": "GET",
  "name": "pci_scan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pci-class-blacklist": {
        "default": "05;06;0b",
        "description": "A list of blacklisted PCI classes, which will not be returned. Following are filtered by default: Memory Controller (05), Bridge (06) and Processor (0b).",
        "format": "string-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "verbose": {
        "default": 1,
        "description": "If disabled, does only print the PCI IDs. Otherwise, additional information like vendor and device will be returned.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit",
        "Sys.Modify"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "class": {
          "description": "The PCI Class of the device.",
          "type": "string"
        },
        "device": {
          "description": "The Device ID.",
          "type": "string"
        },
        "device_name": {
          "optional": 1,
          "type": "string"
        },
        "id": {
          "description": "The PCI ID.",
          "type": "string"
        },
        "iommugroup": {
          "description": "The IOMMU group in which the device is in. If no IOMMU group is detected, it is set to -1.",
          "type": "integer"
        },
        "mdev": {
          "description": "If set, marks that the device is capable of creating mediated devices.",
          "optional": 1,
          "type": "boolean"
        },
        "subsystem_device": {
          "description": "The Subsystem Device ID.",
          "optional": 1,
          "type": "string"
        },
        "subsystem_device_name": {
          "optional": 1,
          "type": "string"
        },
        "subsystem_vendor": {
          "description": "The Subsystem Vendor ID.",
          "optional": 1,
          "type": "string"
        },
        "subsystem_vendor_name": {
          "optional": 1,
          "type": "string"
        },
        "vendor": {
          "description": "The Vendor ID.",
          "type": "string"
        },
        "vendor_name": {
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
