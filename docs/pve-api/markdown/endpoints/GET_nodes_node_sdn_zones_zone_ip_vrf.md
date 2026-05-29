# GET /nodes/{node}/sdn/zones/{zone}/ip-vrf

Get the IP VRF of an EVPN zone.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| zone | string | yes | Name of an EVPN zone. |

## Request parameters

None.

## Returns

```json
{
  "description": "All entries in the VRF table of zone {zone} of the node.This does not include /32 routes for guests on this host,since they are handled via the respective vnet bridge directly.",
  "items": {
    "properties": {
      "ip": {
        "description": "The CIDR of the route table entry.",
        "format": "CIDR",
        "type": "string"
      },
      "metric": {
        "description": "This route's metric.",
        "type": "integer"
      },
      "nexthops": {
        "description": "A list of nexthops for the route table entry.",
        "items": {
          "description": "the interface name or ip address of the next hop",
          "type": "string"
        },
        "type": "array"
      },
      "protocol": {
        "description": "The protocol where this route was learned from (e.g. BGP).",
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
  "description": "Get the IP VRF of an EVPN zone.",
  "method": "GET",
  "name": "ip-vrf",
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
        "description": "Name of an EVPN zone.",
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
    "description": "All entries in the VRF table of zone {zone} of the node.This does not include /32 routes for guests on this host,since they are handled via the respective vnet bridge directly.",
    "items": {
      "properties": {
        "ip": {
          "description": "The CIDR of the route table entry.",
          "format": "CIDR",
          "type": "string"
        },
        "metric": {
          "description": "This route's metric.",
          "type": "integer"
        },
        "nexthops": {
          "description": "A list of nexthops for the route table entry.",
          "items": {
            "description": "the interface name or ip address of the next hop",
            "type": "string"
          },
          "type": "array"
        },
        "protocol": {
          "description": "The protocol where this route was learned from (e.g. BGP).",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
