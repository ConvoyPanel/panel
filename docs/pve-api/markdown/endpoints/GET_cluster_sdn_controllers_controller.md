# GET /cluster/sdn/controllers/{controller}

Read sdn controller configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| controller | string | yes | The SDN controller object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |

## Returns

```json
{
  "properties": {
    "asn": {
      "description": "The local ASN of the controller. BGP & EVPN only.",
      "maximum": 4294967295,
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "bgp-mode": {
      "default": "auto",
      "description": "Whether to use eBGP or iBGP. Auto mode chooses depending on BGP controller or falls back to iBGP.",
      "enum": [
        "auto",
        "external",
        "internal"
      ],
      "optional": 1,
      "type": "string"
    },
    "bgp-multipath-as-relax": {
      "description": "Consider different AS paths of equal length for multipath computation. BGP only.",
      "optional": 1,
      "type": "boolean"
    },
    "controller": {
      "description": "Name of the controller.",
      "type": "string"
    },
    "digest": {
      "description": "Digest of the controller section.",
      "optional": 1,
      "type": "string"
    },
    "ebgp": {
      "description": "Enable eBGP (remote-as external). BGP only.",
      "optional": 1,
      "type": "boolean"
    },
    "ebgp-multihop": {
      "description": "Set maximum amount of hops for eBGP peers. Needs ebgp set to 1. BGP only.",
      "optional": 1,
      "type": "integer"
    },
    "isis-domain": {
      "description": "Name of the IS-IS domain. IS-IS only.",
      "optional": 1,
      "type": "string"
    },
    "isis-ifaces": {
      "description": "Comma-separated list of interfaces where IS-IS should be active. IS-IS only.",
      "format": "pve-iface-list",
      "optional": 1,
      "type": "string"
    },
    "isis-net": {
      "description": "Network Entity title for this node in the IS-IS network. IS-IS only.",
      "format": "pve-sdn-isis-net",
      "optional": 1,
      "type": "string"
    },
    "loopback": {
      "description": "Name of the loopback/dummy interface that provides the Router-IP. BGP only.",
      "optional": 1,
      "type": "string"
    },
    "node": {
      "description": "Node(s) where this controller is active.",
      "optional": 1,
      "type": "string"
    },
    "nodes": {
      "description": "List of cluster node names.",
      "format": "pve-node-list",
      "optional": 1,
      "type": "string"
    },
    "peer-group-name": {
      "description": "Name of the peer group for this EVPN controller",
      "optional": 1,
      "type": "string"
    },
    "peers": {
      "description": "Comma-separated list of the peers IP addresses.",
      "optional": 1,
      "type": "string"
    },
    "pending": {
      "description": "Changes that have not yet been applied to the running configuration.",
      "optional": 1,
      "properties": {
        "asn": {
          "description": "The local ASN of the controller. BGP & EVPN only.",
          "maximum": 4294967295,
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        },
        "bgp-mode": {
          "default": "auto",
          "description": "Whether to use eBGP or iBGP. Auto mode chooses depending on BGP controller or falls back to iBGP.",
          "enum": [
            "auto",
            "external",
            "internal"
          ],
          "optional": 1,
          "type": "string"
        },
        "bgp-multipath-as-relax": {
          "description": "Consider different AS paths of equal length for multipath computation. BGP only.",
          "optional": 1,
          "type": "boolean"
        },
        "ebgp": {
          "description": "Enable eBGP (remote-as external). BGP only.",
          "optional": 1,
          "type": "boolean"
        },
        "ebgp-multihop": {
          "description": "Set maximum amount of hops for eBGP peers. Needs ebgp set to 1. BGP only.",
          "optional": 1,
          "type": "integer"
        },
        "isis-domain": {
          "description": "Name of the IS-IS domain. IS-IS only.",
          "optional": 1,
          "type": "string"
        },
        "isis-ifaces": {
          "description": "Comma-separated list of interfaces where IS-IS should be active. IS-IS only.",
          "format": "pve-iface-list",
          "optional": 1,
          "type": "string"
        },
        "isis-net": {
          "description": "Network Entity title for this node in the IS-IS network. IS-IS only.",
          "format": "pve-sdn-isis-net",
          "optional": 1,
          "type": "string"
        },
        "loopback": {
          "description": "Name of the loopback/dummy interface that provides the Router-IP. BGP only.",
          "optional": 1,
          "type": "string"
        },
        "node": {
          "description": "Node(s) where this controller is active.",
          "optional": 1,
          "type": "string"
        },
        "nodes": {
          "description": "List of cluster node names.",
          "format": "pve-node-list",
          "optional": 1,
          "type": "string"
        },
        "peer-group-name": {
          "description": "Name of the peer group for this EVPN controller",
          "optional": 1,
          "type": "string"
        },
        "peers": {
          "description": "Comma-separated list of the peers IP addresses.",
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
    "type": {
      "description": "Type of the controller",
      "enum": [
        "bgp",
        "evpn",
        "faucet",
        "isis"
      ],
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
    "/sdn/controllers/{controller}",
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
  "description": "Read sdn controller configuration.",
  "method": "GET",
  "name": "read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "controller": {
        "description": "The SDN controller object identifier.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
        "type": "string"
      },
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
    "check": [
      "perm",
      "/sdn/controllers/{controller}",
      [
        "SDN.Allocate"
      ]
    ]
  },
  "returns": {
    "properties": {
      "asn": {
        "description": "The local ASN of the controller. BGP & EVPN only.",
        "maximum": 4294967295,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "bgp-mode": {
        "default": "auto",
        "description": "Whether to use eBGP or iBGP. Auto mode chooses depending on BGP controller or falls back to iBGP.",
        "enum": [
          "auto",
          "external",
          "internal"
        ],
        "optional": 1,
        "type": "string"
      },
      "bgp-multipath-as-relax": {
        "description": "Consider different AS paths of equal length for multipath computation. BGP only.",
        "optional": 1,
        "type": "boolean"
      },
      "controller": {
        "description": "Name of the controller.",
        "type": "string"
      },
      "digest": {
        "description": "Digest of the controller section.",
        "optional": 1,
        "type": "string"
      },
      "ebgp": {
        "description": "Enable eBGP (remote-as external). BGP only.",
        "optional": 1,
        "type": "boolean"
      },
      "ebgp-multihop": {
        "description": "Set maximum amount of hops for eBGP peers. Needs ebgp set to 1. BGP only.",
        "optional": 1,
        "type": "integer"
      },
      "isis-domain": {
        "description": "Name of the IS-IS domain. IS-IS only.",
        "optional": 1,
        "type": "string"
      },
      "isis-ifaces": {
        "description": "Comma-separated list of interfaces where IS-IS should be active. IS-IS only.",
        "format": "pve-iface-list",
        "optional": 1,
        "type": "string"
      },
      "isis-net": {
        "description": "Network Entity title for this node in the IS-IS network. IS-IS only.",
        "format": "pve-sdn-isis-net",
        "optional": 1,
        "type": "string"
      },
      "loopback": {
        "description": "Name of the loopback/dummy interface that provides the Router-IP. BGP only.",
        "optional": 1,
        "type": "string"
      },
      "node": {
        "description": "Node(s) where this controller is active.",
        "optional": 1,
        "type": "string"
      },
      "nodes": {
        "description": "List of cluster node names.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string"
      },
      "peer-group-name": {
        "description": "Name of the peer group for this EVPN controller",
        "optional": 1,
        "type": "string"
      },
      "peers": {
        "description": "Comma-separated list of the peers IP addresses.",
        "optional": 1,
        "type": "string"
      },
      "pending": {
        "description": "Changes that have not yet been applied to the running configuration.",
        "optional": 1,
        "properties": {
          "asn": {
            "description": "The local ASN of the controller. BGP & EVPN only.",
            "maximum": 4294967295,
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          },
          "bgp-mode": {
            "default": "auto",
            "description": "Whether to use eBGP or iBGP. Auto mode chooses depending on BGP controller or falls back to iBGP.",
            "enum": [
              "auto",
              "external",
              "internal"
            ],
            "optional": 1,
            "type": "string"
          },
          "bgp-multipath-as-relax": {
            "description": "Consider different AS paths of equal length for multipath computation. BGP only.",
            "optional": 1,
            "type": "boolean"
          },
          "ebgp": {
            "description": "Enable eBGP (remote-as external). BGP only.",
            "optional": 1,
            "type": "boolean"
          },
          "ebgp-multihop": {
            "description": "Set maximum amount of hops for eBGP peers. Needs ebgp set to 1. BGP only.",
            "optional": 1,
            "type": "integer"
          },
          "isis-domain": {
            "description": "Name of the IS-IS domain. IS-IS only.",
            "optional": 1,
            "type": "string"
          },
          "isis-ifaces": {
            "description": "Comma-separated list of interfaces where IS-IS should be active. IS-IS only.",
            "format": "pve-iface-list",
            "optional": 1,
            "type": "string"
          },
          "isis-net": {
            "description": "Network Entity title for this node in the IS-IS network. IS-IS only.",
            "format": "pve-sdn-isis-net",
            "optional": 1,
            "type": "string"
          },
          "loopback": {
            "description": "Name of the loopback/dummy interface that provides the Router-IP. BGP only.",
            "optional": 1,
            "type": "string"
          },
          "node": {
            "description": "Node(s) where this controller is active.",
            "optional": 1,
            "type": "string"
          },
          "nodes": {
            "description": "List of cluster node names.",
            "format": "pve-node-list",
            "optional": 1,
            "type": "string"
          },
          "peer-group-name": {
            "description": "Name of the peer group for this EVPN controller",
            "optional": 1,
            "type": "string"
          },
          "peers": {
            "description": "Comma-separated list of the peers IP addresses.",
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
      "type": {
        "description": "Type of the controller",
        "enum": [
          "bgp",
          "evpn",
          "faucet",
          "isis"
        ],
        "type": "string"
      }
    }
  }
}
```
