# GET /cluster/sdn/zones/{zone}

Read sdn zone configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| zone | string | yes | The SDN zone object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |

## Returns

```json
{
  "properties": {
    "advertise-subnets": {
      "description": "Advertise IP prefixes (Type-5 routes) instead of MAC/IP pairs (Type-2 routes). EVPN zone only.",
      "optional": 1,
      "type": "boolean"
    },
    "bridge": {
      "description": "the bridge for which VLANs should be managed. VLAN & QinQ zone only.",
      "optional": 1,
      "type": "string"
    },
    "bridge-disable-mac-learning": {
      "description": "Disable auto mac learning. VLAN zone only.",
      "optional": 1,
      "type": "boolean"
    },
    "controller": {
      "description": "ID of the controller for this zone. EVPN zone only.",
      "optional": 1,
      "type": "string"
    },
    "dhcp": {
      "description": "Name of DHCP server backend for this zone.",
      "enum": [
        "dnsmasq"
      ],
      "optional": 1,
      "type": "string"
    },
    "digest": {
      "description": "Digest of the controller section.",
      "optional": 1,
      "type": "string"
    },
    "disable-arp-nd-suppression": {
      "description": "Suppress IPv4 ARP && IPv6 Neighbour Discovery messages. EVPN zone only.",
      "optional": 1,
      "type": "boolean"
    },
    "dns": {
      "description": "ID of the DNS server for this zone.",
      "optional": 1,
      "type": "string"
    },
    "dnszone": {
      "description": "Domain name for this zone.",
      "optional": 1,
      "type": "string"
    },
    "exitnodes": {
      "description": "List of PVE Nodes that should act as exit node for this zone. EVPN zone only.",
      "format": "pve-node-list",
      "optional": 1,
      "type": "string"
    },
    "exitnodes-local-routing": {
      "description": "Create routes on the exit nodes, so they can connect to EVPN guests. EVPN zone only.",
      "optional": 1,
      "type": "boolean"
    },
    "exitnodes-primary": {
      "description": "Force traffic through this exitnode first. EVPN zone only.",
      "format": "pve-node",
      "optional": 1,
      "type": "string"
    },
    "ipam": {
      "description": "ID of the IPAM for this zone.",
      "optional": 1,
      "type": "string"
    },
    "mac": {
      "description": "MAC address of the anycast router for this zone.",
      "optional": 1,
      "type": "string"
    },
    "mtu": {
      "description": "MTU of the zone, will be used for the created VNet bridges.",
      "optional": 1,
      "type": "integer"
    },
    "nodes": {
      "description": "Nodes where this zone should be created.",
      "optional": 1,
      "type": "string"
    },
    "peers": {
      "description": "Comma-separated list of peers, that are part of the VXLAN zone. Usually the IPs of the nodes. VXLAN zone only.",
      "format": "ip-list",
      "optional": 1,
      "type": "string"
    },
    "pending": {
      "description": "Changes that have not yet been applied to the running configuration.",
      "optional": 1,
      "properties": {
        "advertise-subnets": {
          "description": "Advertise IP prefixes (Type-5 routes) instead of MAC/IP pairs (Type-2 routes). EVPN zone only.",
          "optional": 1,
          "type": "boolean"
        },
        "bridge": {
          "description": "the bridge for which VLANs should be managed. VLAN & QinQ zone only.",
          "optional": 1,
          "type": "string"
        },
        "bridge-disable-mac-learning": {
          "description": "Disable auto mac learning. VLAN zone only.",
          "optional": 1,
          "type": "boolean"
        },
        "controller": {
          "description": "ID of the controller for this zone. EVPN zone only.",
          "optional": 1,
          "type": "string"
        },
        "dhcp": {
          "description": "Name of DHCP server backend for this zone.",
          "enum": [
            "dnsmasq"
          ],
          "optional": 1,
          "type": "string"
        },
        "disable-arp-nd-suppression": {
          "description": "Suppress IPv4 ARP && IPv6 Neighbour Discovery messages. EVPN zone only.",
          "optional": 1,
          "type": "boolean"
        },
        "dns": {
          "description": "ID of the DNS server for this zone.",
          "optional": 1,
          "type": "string"
        },
        "dnszone": {
          "description": "Domain name for this zone.",
          "optional": 1,
          "type": "string"
        },
        "exitnodes": {
          "description": "List of PVE Nodes that should act as exit node for this zone. EVPN zone only.",
          "format": "pve-node-list",
          "optional": 1,
          "type": "string"
        },
        "exitnodes-local-routing": {
          "description": "Create routes on the exit nodes, so they can connect to EVPN guests. EVPN zone only.",
          "optional": 1,
          "type": "boolean"
        },
        "exitnodes-primary": {
          "description": "Force traffic through this exitnode first. EVPN zone only.",
          "format": "pve-node",
          "optional": 1,
          "type": "string"
        },
        "ipam": {
          "description": "ID of the IPAM for this zone.",
          "optional": 1,
          "type": "string"
        },
        "mac": {
          "description": "MAC address of the anycast router for this zone.",
          "optional": 1,
          "type": "string"
        },
        "mtu": {
          "description": "MTU of the zone, will be used for the created VNet bridges.",
          "optional": 1,
          "type": "integer"
        },
        "nodes": {
          "description": "Nodes where this zone should be created.",
          "optional": 1,
          "type": "string"
        },
        "peers": {
          "description": "Comma-separated list of peers, that are part of the VXLAN zone. Usually the IPs of the nodes. VXLAN zone only.",
          "format": "ip-list",
          "optional": 1,
          "type": "string"
        },
        "reversedns": {
          "description": "ID of the reverse DNS server for this zone.",
          "optional": 1,
          "type": "string"
        },
        "rt-import": {
          "description": "Route-Targets that should be imported into the VRF of this zone via BGP. EVPN zone only.",
          "format": "pve-sdn-bgp-rt-list",
          "optional": 1,
          "type": "string"
        },
        "secondary-controllers": {
          "description": "Additional controllers.",
          "items": {
            "description": "Controller ID.",
            "maxLength": 64,
            "minLength": 2,
            "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "tag": {
          "description": "Service-VLAN Tag (outer VLAN). QinQ zone only",
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        },
        "vlan-protocol": {
          "default": "802.1q",
          "description": "VLAN protocol for the creation of the QinQ zone. QinQ zone only.",
          "enum": [
            "802.1q",
            "802.1ad"
          ],
          "optional": 1,
          "type": "string"
        },
        "vrf-vxlan": {
          "description": "VNI for the zone VRF. EVPN zone only.",
          "maximum": 16777215,
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        },
        "vxlan-port": {
          "default": 4789,
          "description": "UDP port that should be used for the VXLAN tunnel (default 4789). VXLAN zone only.",
          "maximum": 65536,
          "minimum": 1,
          "optional": 1,
          "type": "integer"
        }
      },
      "type": "object"
    },
    "reversedns": {
      "description": "ID of the reverse DNS server for this zone.",
      "optional": 1,
      "type": "string"
    },
    "rt-import": {
      "description": "Route-Targets that should be imported into the VRF of this zone via BGP. EVPN zone only.",
      "format": "pve-sdn-bgp-rt-list",
      "optional": 1,
      "type": "string"
    },
    "secondary-controllers": {
      "description": "Additional controllers.",
      "items": {
        "description": "Controller ID.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
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
      "description": "Service-VLAN Tag (outer VLAN). QinQ zone only",
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "type": {
      "description": "Type of the zone.",
      "enum": [
        "evpn",
        "faucet",
        "qinq",
        "simple",
        "vlan",
        "vxlan"
      ],
      "type": "string"
    },
    "vlan-protocol": {
      "default": "802.1q",
      "description": "VLAN protocol for the creation of the QinQ zone. QinQ zone only.",
      "enum": [
        "802.1q",
        "802.1ad"
      ],
      "optional": 1,
      "type": "string"
    },
    "vrf-vxlan": {
      "description": "VNI for the zone VRF. EVPN zone only.",
      "maximum": 16777215,
      "minimum": 1,
      "optional": 1,
      "type": "integer"
    },
    "vxlan-port": {
      "default": 4789,
      "description": "UDP port that should be used for the VXLAN tunnel (default 4789). VXLAN zone only.",
      "maximum": 65536,
      "minimum": 1,
      "optional": 1,
      "type": "integer"
    },
    "zone": {
      "description": "Name of the zone.",
      "type": "string"
    }
  }
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/zones/{zone}",
    [
      "SDN.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read sdn zone configuration.",
  "method": "GET",
  "name": "read",
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
      },
      "zone": {
        "description": "The SDN zone object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/zones/{zone}",
      [
        "SDN.Allocate"
      ]
    ]
  },
  "returns": {
    "properties": {
      "advertise-subnets": {
        "description": "Advertise IP prefixes (Type-5 routes) instead of MAC/IP pairs (Type-2 routes). EVPN zone only.",
        "optional": 1,
        "type": "boolean"
      },
      "bridge": {
        "description": "the bridge for which VLANs should be managed. VLAN & QinQ zone only.",
        "optional": 1,
        "type": "string"
      },
      "bridge-disable-mac-learning": {
        "description": "Disable auto mac learning. VLAN zone only.",
        "optional": 1,
        "type": "boolean"
      },
      "controller": {
        "description": "ID of the controller for this zone. EVPN zone only.",
        "optional": 1,
        "type": "string"
      },
      "dhcp": {
        "description": "Name of DHCP server backend for this zone.",
        "enum": [
          "dnsmasq"
        ],
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Digest of the controller section.",
        "optional": 1,
        "type": "string"
      },
      "disable-arp-nd-suppression": {
        "description": "Suppress IPv4 ARP && IPv6 Neighbour Discovery messages. EVPN zone only.",
        "optional": 1,
        "type": "boolean"
      },
      "dns": {
        "description": "ID of the DNS server for this zone.",
        "optional": 1,
        "type": "string"
      },
      "dnszone": {
        "description": "Domain name for this zone.",
        "optional": 1,
        "type": "string"
      },
      "exitnodes": {
        "description": "List of PVE Nodes that should act as exit node for this zone. EVPN zone only.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string"
      },
      "exitnodes-local-routing": {
        "description": "Create routes on the exit nodes, so they can connect to EVPN guests. EVPN zone only.",
        "optional": 1,
        "type": "boolean"
      },
      "exitnodes-primary": {
        "description": "Force traffic through this exitnode first. EVPN zone only.",
        "format": "pve-node",
        "optional": 1,
        "type": "string"
      },
      "ipam": {
        "description": "ID of the IPAM for this zone.",
        "optional": 1,
        "type": "string"
      },
      "mac": {
        "description": "MAC address of the anycast router for this zone.",
        "optional": 1,
        "type": "string"
      },
      "mtu": {
        "description": "MTU of the zone, will be used for the created VNet bridges.",
        "optional": 1,
        "type": "integer"
      },
      "nodes": {
        "description": "Nodes where this zone should be created.",
        "optional": 1,
        "type": "string"
      },
      "peers": {
        "description": "Comma-separated list of peers, that are part of the VXLAN zone. Usually the IPs of the nodes. VXLAN zone only.",
        "format": "ip-list",
        "optional": 1,
        "type": "string"
      },
      "pending": {
        "description": "Changes that have not yet been applied to the running configuration.",
        "optional": 1,
        "properties": {
          "advertise-subnets": {
            "description": "Advertise IP prefixes (Type-5 routes) instead of MAC/IP pairs (Type-2 routes). EVPN zone only.",
            "optional": 1,
            "type": "boolean"
          },
          "bridge": {
            "description": "the bridge for which VLANs should be managed. VLAN & QinQ zone only.",
            "optional": 1,
            "type": "string"
          },
          "bridge-disable-mac-learning": {
            "description": "Disable auto mac learning. VLAN zone only.",
            "optional": 1,
            "type": "boolean"
          },
          "controller": {
            "description": "ID of the controller for this zone. EVPN zone only.",
            "optional": 1,
            "type": "string"
          },
          "dhcp": {
            "description": "Name of DHCP server backend for this zone.",
            "enum": [
              "dnsmasq"
            ],
            "optional": 1,
            "type": "string"
          },
          "disable-arp-nd-suppression": {
            "description": "Suppress IPv4 ARP && IPv6 Neighbour Discovery messages. EVPN zone only.",
            "optional": 1,
            "type": "boolean"
          },
          "dns": {
            "description": "ID of the DNS server for this zone.",
            "optional": 1,
            "type": "string"
          },
          "dnszone": {
            "description": "Domain name for this zone.",
            "optional": 1,
            "type": "string"
          },
          "exitnodes": {
            "description": "List of PVE Nodes that should act as exit node for this zone. EVPN zone only.",
            "format": "pve-node-list",
            "optional": 1,
            "type": "string"
          },
          "exitnodes-local-routing": {
            "description": "Create routes on the exit nodes, so they can connect to EVPN guests. EVPN zone only.",
            "optional": 1,
            "type": "boolean"
          },
          "exitnodes-primary": {
            "description": "Force traffic through this exitnode first. EVPN zone only.",
            "format": "pve-node",
            "optional": 1,
            "type": "string"
          },
          "ipam": {
            "description": "ID of the IPAM for this zone.",
            "optional": 1,
            "type": "string"
          },
          "mac": {
            "description": "MAC address of the anycast router for this zone.",
            "optional": 1,
            "type": "string"
          },
          "mtu": {
            "description": "MTU of the zone, will be used for the created VNet bridges.",
            "optional": 1,
            "type": "integer"
          },
          "nodes": {
            "description": "Nodes where this zone should be created.",
            "optional": 1,
            "type": "string"
          },
          "peers": {
            "description": "Comma-separated list of peers, that are part of the VXLAN zone. Usually the IPs of the nodes. VXLAN zone only.",
            "format": "ip-list",
            "optional": 1,
            "type": "string"
          },
          "reversedns": {
            "description": "ID of the reverse DNS server for this zone.",
            "optional": 1,
            "type": "string"
          },
          "rt-import": {
            "description": "Route-Targets that should be imported into the VRF of this zone via BGP. EVPN zone only.",
            "format": "pve-sdn-bgp-rt-list",
            "optional": 1,
            "type": "string"
          },
          "secondary-controllers": {
            "description": "Additional controllers.",
            "items": {
              "description": "Controller ID.",
              "maxLength": 64,
              "minLength": 2,
              "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          },
          "tag": {
            "description": "Service-VLAN Tag (outer VLAN). QinQ zone only",
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          },
          "vlan-protocol": {
            "default": "802.1q",
            "description": "VLAN protocol for the creation of the QinQ zone. QinQ zone only.",
            "enum": [
              "802.1q",
              "802.1ad"
            ],
            "optional": 1,
            "type": "string"
          },
          "vrf-vxlan": {
            "description": "VNI for the zone VRF. EVPN zone only.",
            "maximum": 16777215,
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "vxlan-port": {
            "default": 4789,
            "description": "UDP port that should be used for the VXLAN tunnel (default 4789). VXLAN zone only.",
            "maximum": 65536,
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          }
        },
        "type": "object"
      },
      "reversedns": {
        "description": "ID of the reverse DNS server for this zone.",
        "optional": 1,
        "type": "string"
      },
      "rt-import": {
        "description": "Route-Targets that should be imported into the VRF of this zone via BGP. EVPN zone only.",
        "format": "pve-sdn-bgp-rt-list",
        "optional": 1,
        "type": "string"
      },
      "secondary-controllers": {
        "description": "Additional controllers.",
        "items": {
          "description": "Controller ID.",
          "maxLength": 64,
          "minLength": 2,
          "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
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
        "description": "Service-VLAN Tag (outer VLAN). QinQ zone only",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "type": {
        "description": "Type of the zone.",
        "enum": [
          "evpn",
          "faucet",
          "qinq",
          "simple",
          "vlan",
          "vxlan"
        ],
        "type": "string"
      },
      "vlan-protocol": {
        "default": "802.1q",
        "description": "VLAN protocol for the creation of the QinQ zone. QinQ zone only.",
        "enum": [
          "802.1q",
          "802.1ad"
        ],
        "optional": 1,
        "type": "string"
      },
      "vrf-vxlan": {
        "description": "VNI for the zone VRF. EVPN zone only.",
        "maximum": 16777215,
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "vxlan-port": {
        "default": 4789,
        "description": "UDP port that should be used for the VXLAN tunnel (default 4789). VXLAN zone only.",
        "maximum": 65536,
        "minimum": 1,
        "optional": 1,
        "type": "integer"
      },
      "zone": {
        "description": "Name of the zone.",
        "type": "string"
      }
    }
  }
}
```
