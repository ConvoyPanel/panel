# GET /nodes/{node}/hardware/pci/{pci-id-or-mapping}/mdev

List mediated device types for given PCI device.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| pci-id-or-mapping | string | yes | The PCI ID or mapping to list the mdev types for. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "available": {
        "description": "The number of still available instances of this type.",
        "type": "integer"
      },
      "description": {
        "description": "Additional description of the type.",
        "type": "string"
      },
      "name": {
        "description": "A human readable name for the type.",
        "optional": 1,
        "type": "string"
      },
      "type": {
        "description": "The name of the mdev type.",
        "type": "string"
      }
    },
    "type": "object"
  },
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
  "description": "List mediated device types for given PCI device.",
  "method": "GET",
  "name": "mdevscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pci-id-or-mapping": {
        "description": "The PCI ID or mapping to list the mdev types for.",
        "pattern": "(?:(?:[0-9a-fA-F]{4}:)?[0-9a-fA-F]{2}:[0-9a-fA-F]{2}\\.[0-9a-fA-F])|([a-zA-Z][a-zA-Z0-9_-]+)",
        "type": "string"
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
        "available": {
          "description": "The number of still available instances of this type.",
          "type": "integer"
        },
        "description": {
          "description": "Additional description of the type.",
          "type": "string"
        },
        "name": {
          "description": "A human readable name for the type.",
          "optional": 1,
          "type": "string"
        },
        "type": {
          "description": "The name of the mdev type.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
