# GET /nodes/{node}/sdn/vnets/{vnet}/mac-vrf

Get the MAC VRF for a VNet in an EVPN zone.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

None.

## Returns

```json
{
  "description": "All routes from the MAC VRF that this node self-originates or has learned via BGP.",
  "items": {
    "properties": {
      "ip": {
        "description": "The IP address of the MAC VRF entry.",
        "format": "ip",
        "type": "string"
      },
      "mac": {
        "description": "The MAC address of the MAC VRF entry.",
        "format": "mac-addr",
        "type": "string"
      },
      "nexthop": {
        "description": "The IP address of the nexthop.",
        "format": "ip",
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
  "description": "Require 'SDN.Audit' permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get the MAC VRF for a VNet in an EVPN zone.",
  "method": "GET",
  "name": "mac-vrf",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vnet": {
        "description": "The SDN vnet object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Require 'SDN.Audit' permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "All routes from the MAC VRF that this node self-originates or has learned via BGP.",
    "items": {
      "properties": {
        "ip": {
          "description": "The IP address of the MAC VRF entry.",
          "format": "ip",
          "type": "string"
        },
        "mac": {
          "description": "The MAC address of the MAC VRF entry.",
          "format": "mac-addr",
          "type": "string"
        },
        "nexthop": {
          "description": "The IP address of the nexthop.",
          "format": "ip",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
