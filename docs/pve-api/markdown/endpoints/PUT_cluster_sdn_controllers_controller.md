# PUT /cluster/sdn/controllers/{controller}

Update sdn controller object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| controller | string | yes | The SDN controller object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| asn | integer | no | autonomous system number |
| bgp-mode | string | no | Whether to use eBGP or iBGP. Auto mode chooses depending on BGP controller or falls back to iBGP. |
| bgp-multipath-as-path-relax | boolean | no | Consider different AS paths of equal length for multipath computation. |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| ebgp | boolean | no | Enable eBGP (remote-as external). |
| ebgp-multihop | integer | no | Set maximum amount of hops for eBGP peers. |
| fabric | string | no | SDN fabric to use as underlay for this EVPN controller. |
| isis-domain | string | no | Name of the IS-IS domain. |
| isis-ifaces | string | no | Comma-separated list of interfaces where IS-IS should be active. |
| isis-net | string | no | Network Entity title for this node in the IS-IS network. |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| loopback | string | no | Name of the loopback/dummy interface that provides the Router-IP. |
| node | string | no | The cluster node name. |
| nodes | string | no | List of cluster node names. |
| peer-group-name | string | no | Name of the peer group for this EVPN controller |
| peers | string | no | peers address list. |
| route-map-in | string | no | Route Map that should be applied for incoming routes |
| route-map-out | string | no | Route Map that should be applied for outgoing routes |

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
    "/sdn/controllers",
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
  "description": "Update sdn controller object configuration.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "asn": {
        "description": "autonomous system number",
        "maximum": 4294967295,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 4294967295)"
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
      "bgp-multipath-as-path-relax": {
        "description": "Consider different AS paths of equal length for multipath computation.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "controller": {
        "description": "The SDN controller object identifier.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9_-]*[a-zA-Z0-9]",
        "type": "string"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "ebgp": {
        "description": "Enable eBGP (remote-as external).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "ebgp-multihop": {
        "description": "Set maximum amount of hops for eBGP peers.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "fabric": {
        "description": "SDN fabric to use as underlay for this EVPN controller.",
        "format": "pve-sdn-fabric-id",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "isis-domain": {
        "description": "Name of the IS-IS domain.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "isis-ifaces": {
        "description": "Comma-separated list of interfaces where IS-IS should be active.",
        "format": "pve-iface-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "isis-net": {
        "description": "Network Entity title for this node in the IS-IS network.",
        "format": "pve-sdn-isis-net",
        "maxLength": 50,
        "minLength": 20,
        "optional": 1,
        "pattern": "[a-fA-F0-9]{2}(\\.[a-fA-F0-9]{4}){3,9}\\.[a-fA-F0-9]{2}",
        "type": "string"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "loopback": {
        "description": "Name of the loopback/dummy interface that provides the Router-IP.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "nodes": {
        "description": "List of cluster node names.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "peer-group-name": {
        "default": "VTEP",
        "description": "Name of the peer group for this EVPN controller",
        "format": "pve-configid",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "peers": {
        "description": "peers address list.",
        "format": "ip-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "route-map-in": {
        "description": "Route Map that should be applied for incoming routes",
        "format": "pve-sdn-route-map-id",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "route-map-out": {
        "description": "Route Map that should be applied for outgoing routes",
        "format": "pve-sdn-route-map-id",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/controllers",
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
