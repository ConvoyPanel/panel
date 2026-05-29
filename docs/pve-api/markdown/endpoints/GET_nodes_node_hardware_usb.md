# GET /nodes/{node}/hardware/usb

List local USB devices.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "busnum": {
        "type": "integer"
      },
      "class": {
        "type": "integer"
      },
      "devnum": {
        "type": "integer"
      },
      "level": {
        "type": "integer"
      },
      "manufacturer": {
        "optional": 1,
        "type": "string"
      },
      "port": {
        "type": "integer"
      },
      "prodid": {
        "type": "string"
      },
      "product": {
        "optional": 1,
        "type": "string"
      },
      "serial": {
        "optional": 1,
        "type": "string"
      },
      "speed": {
        "type": "string"
      },
      "usbpath": {
        "optional": 1,
        "type": "string"
      },
      "vendid": {
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
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List local USB devices.",
  "method": "GET",
  "name": "usbscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "busnum": {
          "type": "integer"
        },
        "class": {
          "type": "integer"
        },
        "devnum": {
          "type": "integer"
        },
        "level": {
          "type": "integer"
        },
        "manufacturer": {
          "optional": 1,
          "type": "string"
        },
        "port": {
          "type": "integer"
        },
        "prodid": {
          "type": "string"
        },
        "product": {
          "optional": 1,
          "type": "string"
        },
        "serial": {
          "optional": 1,
          "type": "string"
        },
        "speed": {
          "type": "string"
        },
        "usbpath": {
          "optional": 1,
          "type": "string"
        },
        "vendid": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
