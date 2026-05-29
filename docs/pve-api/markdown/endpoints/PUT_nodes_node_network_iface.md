# PUT /nodes/{node}/network/{iface}

Update network device configuration

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| iface | string | yes | Network interface name. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | yes | Network interface type |
| address | string | no | IP address. |
| address6 | string | no | IP address. |
| autostart | boolean | no | Automatically start interface on boot. |
| bond_mode | string | no | Bonding mode. |
| bond_xmit_hash_policy | string | no | Selects the transmit hash policy to use for slave selection in balance-xor and 802.3ad modes. |
| bond-primary | string | no | Specify the primary interface for active-backup bond. |
| bridge_ports | string | no | Specify the interfaces you want to add to your bridge. |
| bridge_vids | string | no | Specify the allowed VLANs. For example: '2 4 100-200'. Only used if the bridge is VLAN aware. |
| bridge_vlan_aware | boolean | no | Enable bridge vlan support. |
| cidr | string | no | IPv4 CIDR. |
| cidr6 | string | no | IPv6 CIDR. |
| comments | string | no | Comments |
| comments6 | string | no | Comments |
| delete | string | no | A list of settings you want to delete. |
| gateway | string | no | Default gateway address. |
| gateway6 | string | no | Default ipv6 gateway address. |
| mtu | integer | no | MTU. |
| netmask | string | no | Network mask. |
| netmask6 | integer | no | Network mask. |
| ovs_bonds | string | no | Specify the interfaces used by the bonding device. |
| ovs_bridge | string | no | The OVS bridge associated with a OVS port. This is required when you create an OVS port. |
| ovs_options | string | no | OVS interface options. |
| ovs_ports | string | no | Specify the interfaces you want to add to your bridge. |
| ovs_tag | integer | no | Specify a VLan tag (used by OVSPort, OVSIntPort, OVSBond) |
| slaves | string | no | Specify the interfaces used by the bonding device. |
| vlan-id | integer | no | vlan-id for a custom named vlan interface (ifupdown2 only). |
| vlan-raw-device | string | no | Specify the raw interface for the vlan interface. |

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
    "/nodes/{node}",
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
  "description": "Update network device configuration",
  "method": "PUT",
  "name": "update_network",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "address": {
        "description": "IP address.",
        "format": "ipv4",
        "optional": 1,
        "requires": "netmask",
        "type": "string",
        "typetext": "<string>"
      },
      "address6": {
        "description": "IP address.",
        "format": "ipv6",
        "optional": 1,
        "requires": "netmask6",
        "type": "string",
        "typetext": "<string>"
      },
      "autostart": {
        "description": "Automatically start interface on boot.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "bond-primary": {
        "description": "Specify the primary interface for active-backup bond.",
        "format": "pve-iface",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "bond_mode": {
        "description": "Bonding mode.",
        "enum": [
          "balance-rr",
          "active-backup",
          "balance-xor",
          "broadcast",
          "802.3ad",
          "balance-tlb",
          "balance-alb",
          "balance-slb",
          "lacp-balance-slb",
          "lacp-balance-tcp"
        ],
        "optional": 1,
        "type": "string"
      },
      "bond_xmit_hash_policy": {
        "description": "Selects the transmit hash policy to use for slave selection in balance-xor and 802.3ad modes.",
        "enum": [
          "layer2",
          "layer2+3",
          "layer3+4"
        ],
        "optional": 1,
        "type": "string"
      },
      "bridge_ports": {
        "description": "Specify the interfaces you want to add to your bridge.",
        "format": "pve-iface-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "bridge_vids": {
        "description": "Specify the allowed VLANs. For example: '2 4 100-200'. Only used if the bridge is VLAN aware.",
        "format": "pve-vlan-id-or-range-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "bridge_vlan_aware": {
        "description": "Enable bridge vlan support.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "cidr": {
        "description": "IPv4 CIDR.",
        "format": "CIDRv4",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "cidr6": {
        "description": "IPv6 CIDR.",
        "format": "CIDRv6",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "comments": {
        "description": "Comments",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "comments6": {
        "description": "Comments",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "gateway": {
        "description": "Default gateway address.",
        "format": "ipv4",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "gateway6": {
        "description": "Default ipv6 gateway address.",
        "format": "ipv6",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "iface": {
        "description": "Network interface name.",
        "format": "pve-iface",
        "maxLength": 20,
        "minLength": 2,
        "type": "string",
        "typetext": "<string>"
      },
      "mtu": {
        "description": "MTU.",
        "maximum": 65520,
        "minimum": 1280,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1280 - 65520)"
      },
      "netmask": {
        "description": "Network mask.",
        "format": "ipv4mask",
        "optional": 1,
        "requires": "address",
        "type": "string",
        "typetext": "<string>"
      },
      "netmask6": {
        "description": "Network mask.",
        "maximum": 128,
        "minimum": 0,
        "optional": 1,
        "requires": "address6",
        "type": "integer",
        "typetext": "<integer> (0 - 128)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "ovs_bonds": {
        "description": "Specify the interfaces used by the bonding device.",
        "format": "pve-iface-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ovs_bridge": {
        "description": "The OVS bridge associated with a OVS port. This is required when you create an OVS port.",
        "format": "pve-iface",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ovs_options": {
        "description": "OVS interface options.",
        "maxLength": 1024,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ovs_ports": {
        "description": "Specify the interfaces you want to add to your bridge.",
        "format": "pve-iface-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ovs_tag": {
        "description": "Specify a VLan tag (used by OVSPort, OVSIntPort, OVSBond)",
        "maximum": 4094,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 4094)"
      },
      "slaves": {
        "description": "Specify the interfaces used by the bonding device.",
        "format": "pve-iface-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "Network interface type",
        "enum": [
          "bridge",
          "bond",
          "eth",
          "alias",
          "vlan",
          "fabric",
          "OVSBridge",
          "OVSBond",
          "OVSPort",
          "OVSIntPort",
          "vnet",
          "unknown"
        ],
        "type": "string"
      },
      "vlan-id": {
        "description": "vlan-id for a custom named vlan interface (ifupdown2 only).",
        "maximum": 4094,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 4094)"
      },
      "vlan-raw-device": {
        "description": "Specify the raw interface for the vlan interface.",
        "format": "pve-iface",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
