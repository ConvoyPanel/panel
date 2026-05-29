# POST /cluster/sdn/fabrics/node/{fabric_id}

Add a node

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| fabric_id | string | yes | Identifier for SDN fabrics |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| interfaces | array | yes |  |
| node_id | string | yes | Identifier for nodes in an SDN fabric |
| protocol | string | yes | Type of configuration entry in an SDN Fabric section config |
| allowed_ips | array | no | A list of IPs that are routable via this node in the WireGuard fabric. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| endpoint | string | no | The endpoint used for connecting to this node. |
| ip | string | no | IPv4 address for this node |
| ip6 | string | no | IPv6 address for this node |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| peers | array | no |  |
| public_key | string | no | The public key for the external node. |
| role | string | no | The role of this node in the WireGuard fabric. |

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
    "and",
    [
      "perm",
      "/sdn/fabrics/{fabric_id}",
      [
        "SDN.Allocate"
      ]
    ],
    [
      "perm",
      "/nodes/{node_id}",
      [
        "Sys.Modify"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Add a node",
  "method": "POST",
  "name": "add_node",
  "parameters": {
    "properties": {
      "allowed_ips": {
        "description": "A list of IPs that are routable via this node in the WireGuard fabric.",
        "instance-types": [
          "wireguard"
        ],
        "items": {
          "format": "FullRangeCIDR",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "type-property": "protocol",
        "typetext": "<array>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "endpoint": {
        "description": "The endpoint used for connecting to this node.",
        "instance-types": [
          "wireguard"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "protocol",
        "typetext": "<string>"
      },
      "fabric_id": {
        "description": "Identifier for SDN fabrics",
        "format": "pve-sdn-fabric-id",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z0-9][a-zA-Z0-9-]{0,6}[a-zA-Z0-9]",
        "type": "string"
      },
      "interfaces": {
        "oneOf": [
          {
            "description": "OpenFabric network interface",
            "instance-types": [
              "openfabric"
            ],
            "items": {
              "format": {
                "hello_multiplier": {
                  "description": "The hello_multiplier property of the interface",
                  "maximum": 100,
                  "minimum": 2,
                  "optional": 1,
                  "type": "integer"
                },
                "ip": {
                  "description": "IPv4 address for this node",
                  "format": "CIDRv4",
                  "optional": 1,
                  "type": "string"
                },
                "ip6": {
                  "description": "IPv6 address for this node",
                  "format": "CIDRv6",
                  "optional": 1,
                  "type": "string"
                },
                "name": {
                  "description": "Name of the network interface",
                  "format": "pve-iface",
                  "type": "string"
                }
              },
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          },
          {
            "description": "OSPF network interface",
            "instance-types": [
              "ospf"
            ],
            "items": {
              "format": {
                "ip": {
                  "description": "IPv4 address for this node",
                  "format": "CIDRv4",
                  "optional": 1,
                  "type": "string"
                },
                "name": {
                  "description": "Name of the network interface",
                  "format": "pve-iface",
                  "type": "string"
                }
              },
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          },
          {
            "description": "List of WireGuard network interfaces for this node.",
            "instance-types": [
              "wireguard"
            ],
            "items": {
              "description": "WireGuard network interface",
              "format": "pve-sdn-fabric-wireguard-interface",
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          },
          {
            "description": "BGP network interface",
            "instance-types": [
              "bgp"
            ],
            "items": {
              "format": {
                "name": {
                  "description": "Name of the network interface",
                  "format": "pve-iface",
                  "type": "string"
                }
              },
              "type": "string"
            },
            "optional": 1
          }
        ],
        "type": "array",
        "type-property": "protocol",
        "typetext": "<array>"
      },
      "ip": {
        "description": "IPv4 address for this node",
        "format": "ipv4",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ip6": {
        "description": "IPv6 address for this node",
        "format": "ipv6",
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
      "node_id": {
        "description": "Identifier for nodes in an SDN fabric",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "peers": {
        "instance-types": [
          "wireguard"
        ],
        "items": {
          "format": {
            "endpoint": {
              "description": "Override for the endpoint settings in the node section.",
              "optional": 1,
              "type": "string"
            },
            "iface": {
              "description": "The interface of this node that uses this peer definition.",
              "type": "string"
            },
            "node": {
              "description": "The name of the referenced node section (the external node or the internal peer node).",
              "type": "string"
            },
            "node_iface": {
              "description": "The interface of the other node, if it is internal",
              "optional": 1,
              "type": "string"
            },
            "skip_route_generation": {
              "default": 0,
              "description": "Whether routes for the allowed IPs should be created in the kernel routing table.",
              "optional": 1,
              "type": "boolean"
            },
            "type": {
              "enum": [
                "internal",
                "external"
              ],
              "type": "string"
            }
          },
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "type-property": "protocol",
        "typetext": "<array>"
      },
      "protocol": {
        "description": "Type of configuration entry in an SDN Fabric section config",
        "enum": [
          "openfabric",
          "ospf",
          "wireguard",
          "bgp"
        ],
        "type": "string"
      },
      "public_key": {
        "description": "The public key for the external node.",
        "instance-types": [
          "wireguard"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "protocol",
        "typetext": "<string>"
      },
      "role": {
        "description": "The role of this node in the WireGuard fabric.",
        "enum": [
          "internal",
          "external"
        ],
        "instance-types": [
          "wireguard"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "protocol"
      }
    }
  },
  "permissions": {
    "check": [
      "and",
      [
        "perm",
        "/sdn/fabrics/{fabric_id}",
        [
          "SDN.Allocate"
        ]
      ],
      [
        "perm",
        "/nodes/{node_id}",
        [
          "Sys.Modify"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
