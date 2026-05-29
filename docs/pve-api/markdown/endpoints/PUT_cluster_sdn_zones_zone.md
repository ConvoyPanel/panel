# PUT /cluster/sdn/zones/{zone}

Update sdn zone object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| zone | string | yes | The SDN zone object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| advertise-subnets | boolean | no | Advertise IP prefixes (Type-5 routes) instead of MAC/IP pairs (Type-2 routes). |
| bridge | string | no | The bridge for which VLANs should be managed. |
| bridge-disable-mac-learning | boolean | no | Disable auto mac learning. |
| controller | string | no | Controller for this zone. |
| delete | string | no | A list of settings you want to delete. |
| dhcp | string | no | Type of the DHCP backend for this zone |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable-arp-nd-suppression | boolean | no | Suppress IPv4 ARP && IPv6 Neighbour Discovery messages. |
| dns | string | no | dns api server |
| dnszone | string | no | dns domain zone  ex: mydomain.com |
| dp-id | integer | no | Faucet dataplane id |
| exitnodes | string | no | List of cluster node names. |
| exitnodes-local-routing | boolean | no | Allow exitnodes to connect to EVPN guests. |
| exitnodes-primary | string | no | Force traffic through this exitnode first. |
| fabric | string | no | SDN fabric to use as underlay for this VXLAN zone. |
| ipam | string | no | use a specific ipam |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| mac | string | no | Anycast logical router mac address. |
| mtu | integer | no | MTU of the zone, will be used for the created VNet bridges. |
| nodes | string | no | List of cluster node names. |
| peers | string | no | Comma-separated list of peers, that are part of the VXLAN zone. Usually the IPs of the nodes. |
| reversedns | string | no | reverse dns api server |
| rt-import | string | no | List of Route Targets that should be imported into the VRF of the zone. |
| secondary-controllers | array | no | Additional controllers. |
| tag | integer | no | Service-VLAN Tag (outer VLAN) |
| vlan-protocol | string | no | Which VLAN protocol should be used for the creation of the QinQ zone. |
| vrf-vxlan | integer | no | VNI for the zone VRF. |
| vxlan-port | integer | no | UDP port that should be used for the VXLAN tunnel (default 4789). |

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
  "description": "Update sdn zone object configuration.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "advertise-subnets": {
        "description": "Advertise IP prefixes (Type-5 routes) instead of MAC/IP pairs (Type-2 routes).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "bridge": {
        "description": "The bridge for which VLANs should be managed.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "bridge-disable-mac-learning": {
        "description": "Disable auto mac learning.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "controller": {
        "description": "Controller for this zone.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dhcp": {
        "description": "Type of the DHCP backend for this zone",
        "enum": [
          "dnsmasq"
        ],
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "disable-arp-nd-suppression": {
        "description": "Suppress IPv4 ARP && IPv6 Neighbour Discovery messages.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "dns": {
        "description": "dns api server",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dnszone": {
        "description": "dns domain zone  ex: mydomain.com",
        "format": "dns-name",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dp-id": {
        "description": "Faucet dataplane id",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "exitnodes": {
        "description": "List of cluster node names.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "exitnodes-local-routing": {
        "description": "Allow exitnodes to connect to EVPN guests.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "exitnodes-primary": {
        "description": "Force traffic through this exitnode first.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "fabric": {
        "description": "SDN fabric to use as underlay for this VXLAN zone.",
        "format": "pve-sdn-fabric-id",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ipam": {
        "description": "use a specific ipam",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "mac": {
        "description": "Anycast logical router mac address.",
        "format": "mac-addr",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "mtu": {
        "description": "MTU of the zone, will be used for the created VNet bridges.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "nodes": {
        "description": "List of cluster node names.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "peers": {
        "description": "Comma-separated list of peers, that are part of the VXLAN zone. Usually the IPs of the nodes.",
        "format": "ip-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "reversedns": {
        "description": "reverse dns api server",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "rt-import": {
        "description": "List of Route Targets that should be imported into the VRF of the zone.",
        "format": "pve-sdn-bgp-rt-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
        "type": "array",
        "typetext": "<array>"
      },
      "tag": {
        "description": "Service-VLAN Tag (outer VLAN)",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "vlan-protocol": {
        "default": "802.1q",
        "description": "Which VLAN protocol should be used for the creation of the QinQ zone.",
        "enum": [
          "802.1q",
          "802.1ad"
        ],
        "optional": 1,
        "type": "string"
      },
      "vrf-vxlan": {
        "description": "VNI for the zone VRF.",
        "maximum": 16777215,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 16777215)"
      },
      "vxlan-port": {
        "default": 4789,
        "description": "UDP port that should be used for the VXLAN tunnel (default 4789).",
        "maximum": 65536,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 65536)"
      },
      "zone": {
        "description": "The SDN zone object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    },
    "type": "object"
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
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
