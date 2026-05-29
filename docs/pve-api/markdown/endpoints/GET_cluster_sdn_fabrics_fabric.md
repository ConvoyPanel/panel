# GET /cluster/sdn/fabrics/fabric

SDN Fabrics Index

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
      "area": {
        "description": "OSPF area. Either a IPv4 address or a 32-bit number. Gets validated in rust.",
        "instance-types": [
          "ospf"
        ],
        "optional": 1,
        "type": "string",
        "type-property": "protocol"
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
        "type-property": "protocol"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string"
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
        "type-property": "protocol"
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
        "type": "string"
      },
      "ip_prefix": {
        "description": "The IP prefix for Node IPs",
        "format": "CIDR",
        "optional": 1,
        "type": "string"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string"
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
        "type-property": "protocol"
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
        "type-property": "protocol"
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
        "type-property": "protocol"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{id}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/fabrics/<fabric>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "SDN Fabrics Index",
  "method": "GET",
  "name": "index",
  "parameters": {
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
    "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/fabrics/<fabric>'",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "area": {
          "description": "OSPF area. Either a IPv4 address or a 32-bit number. Gets validated in rust.",
          "instance-types": [
            "ospf"
          ],
          "optional": 1,
          "type": "string",
          "type-property": "protocol"
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
          "type-property": "protocol"
        },
        "digest": {
          "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
          "maxLength": 64,
          "optional": 1,
          "type": "string"
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
          "type-property": "protocol"
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
          "type": "string"
        },
        "ip_prefix": {
          "description": "The IP prefix for Node IPs",
          "format": "CIDR",
          "optional": 1,
          "type": "string"
        },
        "lock-token": {
          "description": "the token for unlocking the global SDN configuration",
          "optional": 1,
          "type": "string"
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
          "type-property": "protocol"
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
          "type-property": "protocol"
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
          "type-property": "protocol"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
