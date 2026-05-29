# GET /nodes/{node}/lxc/{vmid}/interfaces

Get IP addresses of the specified container interface.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "hardware-address": {
        "description": "The MAC address of the interface",
        "optional": 0,
        "type": "string"
      },
      "hwaddr": {
        "description": "The MAC address of the interface",
        "optional": 0,
        "type": "string"
      },
      "inet": {
        "description": "The IPv4 address of the interface",
        "optional": 1,
        "type": "string"
      },
      "inet6": {
        "description": "The IPv6 address of the interface",
        "optional": 1,
        "type": "string"
      },
      "ip-addresses": {
        "description": "The addresses of the interface",
        "items": {
          "properties": {
            "ip-address": {
              "description": "IP-Address",
              "optional": 1,
              "type": "string"
            },
            "ip-address-type": {
              "description": "IP-Family",
              "optional": 1,
              "type": "string"
            },
            "prefix": {
              "description": "IP-Prefix",
              "optional": 1,
              "type": "integer"
            }
          },
          "type": "object"
        },
        "optional": 0,
        "type": "array"
      },
      "name": {
        "description": "The name of the interface",
        "optional": 0,
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
    "/vms/{vmid}",
    [
      "VM.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get IP addresses of the specified container interface.",
  "method": "GET",
  "name": "ip",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "hardware-address": {
          "description": "The MAC address of the interface",
          "optional": 0,
          "type": "string"
        },
        "hwaddr": {
          "description": "The MAC address of the interface",
          "optional": 0,
          "type": "string"
        },
        "inet": {
          "description": "The IPv4 address of the interface",
          "optional": 1,
          "type": "string"
        },
        "inet6": {
          "description": "The IPv6 address of the interface",
          "optional": 1,
          "type": "string"
        },
        "ip-addresses": {
          "description": "The addresses of the interface",
          "items": {
            "properties": {
              "ip-address": {
                "description": "IP-Address",
                "optional": 1,
                "type": "string"
              },
              "ip-address-type": {
                "description": "IP-Family",
                "optional": 1,
                "type": "string"
              },
              "prefix": {
                "description": "IP-Prefix",
                "optional": 1,
                "type": "integer"
              }
            },
            "type": "object"
          },
          "optional": 0,
          "type": "array"
        },
        "name": {
          "description": "The name of the interface",
          "optional": 0,
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
