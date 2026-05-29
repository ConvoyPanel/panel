# POST /cluster/mapping/usb

Create a new hardware mapping.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the logical USB mapping. |
| map | array | yes | A list of maps for the cluster nodes. |
| description | string | no | Description of the logical USB device. |

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
    "/mapping/usb",
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
        "description": "Description of the logical USB device.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "id": {
        "description": "The ID of the logical USB mapping.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
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
              "description": "The vendor and device ID that is expected. If a USB path is given, it is only used for detecting hardware changes",
              "pattern": "(?^:^[0-9A-Fa-f]{4}:[0-9A-Fa-f]{4}$)",
              "type": "string"
            },
            "node": {
              "description": "The cluster node name.",
              "format": "pve-node",
              "type": "string"
            },
            "path": {
              "description": "The path to the usb device.",
              "optional": 1,
              "pattern": "(?^:^(\\d+)\\-(\\d+(\\.\\d+)*)$)",
              "type": "string"
            }
          },
          "type": "string"
        },
        "type": "array",
        "typetext": "<array>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/usb",
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
