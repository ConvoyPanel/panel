# POST /cluster/sdn/fabrics/fabric

Add a fabric

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Identifier for SDN fabrics |
| protocol | string | yes | Type of configuration entry in an SDN Fabric section config |
| redistribute | array | yes |  |
| area | string | no | OSPF area. Either a IPv4 address or a 32-bit number. Gets validated in rust. |
| csnp_interval | number | no | The csnp_interval property for Openfabric |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| hello_interval | number | no | The hello_interval property for Openfabric |
| ip_prefix | string | no | The IP prefix for Node IPs |
| ip6_prefix | string | no | The IP prefix for Node IPs |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| persistent_keepalive | number | no | A seconds interval, between 1 and 65535 inclusive, of how often to send an authenticated empty packet to the peer for the purpose of keeping a stateful firewall or NAT mapping valid persistently. For example, if the interface very rarely sends traffic, but it might at anytime receive traffic from another node, and it is behind NAT, the interface might benefit from having a persistent keepalive interval of 25 seconds. If unset or set to 0, it is turned off |
| route_filter | string | no | A prefix list that should be used for filtering routes that are to be installed into the kernel routing table |

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
    "/sdn/fabrics",
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
  "description": "Add a fabric",
  "method": "POST",
  "name": "add_fabric",
  "parameters": {
    "properties": {
      "area": {
        "description": "OSPF area. Either a IPv4 address or a 32-bit number. Gets validated in rust.",
        "instance-types": [
          "ospf"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "protocol",
        "typetext": "<string>"
      },
      "csnp_interval": {
        "description": "The csnp_interval property for Openfabric",
        "instance-types": [
          "openfabric"
        ],
        "maximum": 600,
        "minimum": 1,
        "optional": 1,
        "type": "number",
        "type-property": "protocol",
        "typetext": "<number> (1 - 600)"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "hello_interval": {
        "description": "The hello_interval property for Openfabric",
        "instance-types": [
          "openfabric"
        ],
        "maximum": 600,
        "minimum": 1,
        "optional": 1,
        "type": "number",
        "type-property": "protocol",
        "typetext": "<number> (1 - 600)"
      },
      "id": {
        "description": "Identifier for SDN fabrics",
        "format": "pve-sdn-fabric-id",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z0-9][a-zA-Z0-9-]{0,6}[a-zA-Z0-9]",
        "type": "string"
      },
      "ip6_prefix": {
        "description": "The IP prefix for Node IPs",
        "format": "CIDR",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ip_prefix": {
        "description": "The IP prefix for Node IPs",
        "format": "CIDR",
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
      "persistent_keepalive": {
        "description": "A seconds interval, between 1 and 65535 inclusive, of how often to send an authenticated empty packet to the peer for the purpose of keeping a stateful firewall or NAT mapping valid persistently. For example, if the interface very rarely sends traffic, but it might at anytime receive traffic from another node, and it is behind NAT, the interface might benefit from having a persistent keepalive interval of 25 seconds. If unset or set to 0, it is turned off",
        "instance-types": [
          "wireguard"
        ],
        "maximum": 65535,
        "minimum": 0,
        "optional": 1,
        "type": "number",
        "type-property": "protocol",
        "typetext": "<number> (0 - 65535)"
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
      "redistribute": {
        "oneOf": [
          {
            "instance-types": [
              "ospf"
            ],
            "items": {
              "format": {
                "route-map": {
                  "description": "Route map to filter or transform redistributed routes from this source.",
                  "format": "pve-sdn-route-map-id",
                  "optional": 1,
                  "type": "string"
                },
                "source": {
                  "description": "The protocol from which to redistribute routes from.",
                  "enum": [
                    "bgp",
                    "connected",
                    "kernel",
                    "static"
                  ],
                  "type": "string"
                }
              },
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          },
          {
            "instance-types": [
              "bgp"
            ],
            "items": {
              "format": {
                "route-map": {
                  "description": "Route map to filter or transform redistributed routes from this source.",
                  "format": "pve-sdn-route-map-id",
                  "optional": 1,
                  "type": "string"
                },
                "source": {
                  "description": "The protocol from which to redistribute routes from.",
                  "enum": [
                    "connected",
                    "kernel",
                    "ospf",
                    "static"
                  ],
                  "type": "string"
                }
              },
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          }
        ],
        "type": "array",
        "type-property": "protocol",
        "typetext": "<array>"
      },
      "route_filter": {
        "description": "A prefix list that should be used for filtering routes that are to be installed into the kernel routing table",
        "format": "pve-sdn-prefix-list-id",
        "instance-types": [
          "ospf",
          "openfabric"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "protocol",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/fabrics",
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
