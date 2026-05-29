# POST /cluster/sdn/vnets

Create a new sdn vnet object.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |
| zone | string | yes | Name of the zone this VNet belongs to. |
| alias | string | no | Alias name of the VNet. |
| isolate-ports | boolean | no | If true, sets the isolated property for all interfaces on the bridge of this VNet. |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| tag | integer | no | VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones). |
| type | string | no | Type of the VNet. |
| vlanaware | boolean | no | Allow VLANs to pass through this vnet. |

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
  "description": "Create a new sdn vnet object.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "alias": {
        "description": "Alias name of the VNet.",
        "maxLength": 256,
        "optional": 1,
        "pattern": "(?^i:[\\(\\)-_.\\w\\d\\s]{0,256})",
        "type": "string"
      },
      "isolate-ports": {
        "description": "If true, sets the isolated property for all interfaces on the bridge of this VNet.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "tag": {
        "description": "VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones).",
        "maximum": 16777215,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 16777215)"
      },
      "type": {
        "description": "Type of the VNet.",
        "enum": [
          "vnet"
        ],
        "optional": 1,
        "type": "string"
      },
      "vlanaware": {
        "description": "Allow VLANs to pass through this vnet.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "vnet": {
        "description": "The SDN vnet object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      },
      "zone": {
        "description": "Name of the zone this VNet belongs to.",
        "optional": 0,
        "type": "string",
        "typetext": "<string>"
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
