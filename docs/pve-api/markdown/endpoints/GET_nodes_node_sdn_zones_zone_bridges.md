# GET /nodes/{node}/sdn/zones/{zone}/bridges

Get a list of all bridges (vnets) that are part of a zone, as well as the ports that are members of that bridge.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| zone | string | yes | zone name or "localnetwork" |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "description": "List of bridges contained in the SDN zone.",
    "properties": {
      "name": {
        "description": "Name of the bridge.",
        "type": "string"
      },
      "ports": {
        "description": "All ports that are members of the bridge",
        "items": {
          "description": "Information about bridge ports.",
          "properties": {
            "index": {
              "description": "The index of the guests network device that this interface belongs to.",
              "optional": 1,
              "type": "string"
            },
            "name": {
              "description": "The name of the bridge port.",
              "type": "string"
            },
            "primary_vlan": {
              "description": "The primary VLAN configured for the port of this bridge (= PVID). Only for VLAN-aware bridges.",
              "optional": 1,
              "type": "number"
            },
            "vlans": {
              "description": "A list of VLANs and VLAN ranges that are allowed for this bridge port in addition to the primary VLAN. Only for VLAN-aware bridges.",
              "items": {
                "description": "A single VLAN (123) or a VLAN range (234-435).",
                "type": "string"
              },
              "optional": 1,
              "type": "array"
            },
            "vmid": {
              "description": "The ID of the guest that this interface belongs to.",
              "optional": 1,
              "type": "number"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "vlan_filtering": {
        "description": "Whether VLAN filtering is enabled for this bridge (= VLAN-aware).",
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
    "/sdn/zones/{zone}",
    [
      "SDN.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get a list of all bridges (vnets) that are part of a zone, as well as the ports that are members of that bridge.",
  "method": "GET",
  "name": "bridges",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "zone": {
        "description": "zone name or \"localnetwork\"",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/zones/{zone}",
      [
        "SDN.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "description": "List of bridges contained in the SDN zone.",
      "properties": {
        "name": {
          "description": "Name of the bridge.",
          "type": "string"
        },
        "ports": {
          "description": "All ports that are members of the bridge",
          "items": {
            "description": "Information about bridge ports.",
            "properties": {
              "index": {
                "description": "The index of the guests network device that this interface belongs to.",
                "optional": 1,
                "type": "string"
              },
              "name": {
                "description": "The name of the bridge port.",
                "type": "string"
              },
              "primary_vlan": {
                "description": "The primary VLAN configured for the port of this bridge (= PVID). Only for VLAN-aware bridges.",
                "optional": 1,
                "type": "number"
              },
              "vlans": {
                "description": "A list of VLANs and VLAN ranges that are allowed for this bridge port in addition to the primary VLAN. Only for VLAN-aware bridges.",
                "items": {
                  "description": "A single VLAN (123) or a VLAN range (234-435).",
                  "type": "string"
                },
                "optional": 1,
                "type": "array"
              },
              "vmid": {
                "description": "The ID of the guest that this interface belongs to.",
                "optional": 1,
                "type": "number"
              }
            },
            "type": "object"
          },
          "type": "array"
        },
        "vlan_filtering": {
          "description": "Whether VLAN filtering is enabled for this bridge (= VLAN-aware).",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
