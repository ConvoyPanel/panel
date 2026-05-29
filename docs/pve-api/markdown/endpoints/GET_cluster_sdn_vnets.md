# GET /cluster/sdn/vnets

SDN vnets index.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |

## Returns

```json
{
  "items": {
    "properties": {
      "alias": {
        "description": "Alias name of the VNet.",
        "maxLength": 256,
        "optional": 1,
        "pattern": "(?^i:[\\(\\)-_.\\w\\d\\s]{0,256})",
        "type": "string"
      },
      "digest": {
        "description": "Digest of the VNet section.",
        "optional": 1,
        "type": "string"
      },
      "isolate-ports": {
        "description": "If true, sets the isolated property for all interfaces on the bridge of this VNet.",
        "optional": 1,
        "type": "boolean"
      },
      "pending": {
        "description": "Changes that have not yet been applied to the running configuration.",
        "optional": 1,
        "properties": {
          "alias": {
            "description": "Alias name of the VNet.",
            "maxLength": 256,
            "optional": 1,
            "pattern": "(?^i:[\\(\\)-_.\\w\\d\\s]{0,256})",
            "type": "string"
          },
          "isolate-ports": {
            "description": "If true, sets the isolated property for all interfaces on the bridge of this VNet.",
            "optional": 1,
            "type": "boolean"
          },
          "tag": {
            "description": "VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones).",
            "maximum": 16777215,
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "vlanaware": {
            "description": "Allow VLANs to pass through this VNet.",
            "optional": 1,
            "type": "boolean"
          },
          "zone": {
            "description": "Name of the zone this VNet belongs to.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "state": {
        "description": "State of the SDN configuration object.",
        "enum": [
          "new",
          "changed",
          "deleted"
        ],
        "optional": 1,
        "type": "string"
      },
      "tag": {
        "description": "VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones).",
        "maximum": 16777215,
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "type": {
        "description": "Type of the VNet.",
        "enum": [
          "vnet"
        ],
        "optional": 0,
        "type": "string"
      },
      "vlanaware": {
        "description": "Allow VLANs to pass through this VNet.",
        "optional": 1,
        "type": "boolean"
      },
      "vnet": {
        "description": "Name of the VNet.",
        "optional": 0,
        "type": "string"
      },
      "zone": {
        "description": "Name of the zone this VNet belongs to.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{vnet}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "SDN vnets index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "pending": {
        "description": "Display pending config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "running": {
        "description": "Display running config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "alias": {
          "description": "Alias name of the VNet.",
          "maxLength": 256,
          "optional": 1,
          "pattern": "(?^i:[\\(\\)-_.\\w\\d\\s]{0,256})",
          "type": "string"
        },
        "digest": {
          "description": "Digest of the VNet section.",
          "optional": 1,
          "type": "string"
        },
        "isolate-ports": {
          "description": "If true, sets the isolated property for all interfaces on the bridge of this VNet.",
          "optional": 1,
          "type": "boolean"
        },
        "pending": {
          "description": "Changes that have not yet been applied to the running configuration.",
          "optional": 1,
          "properties": {
            "alias": {
              "description": "Alias name of the VNet.",
              "maxLength": 256,
              "optional": 1,
              "pattern": "(?^i:[\\(\\)-_.\\w\\d\\s]{0,256})",
              "type": "string"
            },
            "isolate-ports": {
              "description": "If true, sets the isolated property for all interfaces on the bridge of this VNet.",
              "optional": 1,
              "type": "boolean"
            },
            "tag": {
              "description": "VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones).",
              "maximum": 16777215,
              "minimum": 1,
              "optional": 1,
              "type": "integer"
            },
            "vlanaware": {
              "description": "Allow VLANs to pass through this VNet.",
              "optional": 1,
              "type": "boolean"
            },
            "zone": {
              "description": "Name of the zone this VNet belongs to.",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "state": {
          "description": "State of the SDN configuration object.",
          "enum": [
            "new",
            "changed",
            "deleted"
          ],
          "optional": 1,
          "type": "string"
        },
        "tag": {
          "description": "VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones).",
          "maximum": 16777215,
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "type": {
          "description": "Type of the VNet.",
          "enum": [
            "vnet"
          ],
          "optional": 0,
          "type": "string"
        },
        "vlanaware": {
          "description": "Allow VLANs to pass through this VNet.",
          "optional": 1,
          "type": "boolean"
        },
        "vnet": {
          "description": "Name of the VNet.",
          "optional": 0,
          "type": "string"
        },
        "zone": {
          "description": "Name of the zone this VNet belongs to.",
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{vnet}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
