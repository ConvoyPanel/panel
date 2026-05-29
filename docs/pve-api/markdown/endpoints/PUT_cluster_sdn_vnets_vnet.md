# PUT /cluster/sdn/vnets/{vnet}

Update sdn vnet object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| alias | string | no | Alias name of the VNet. |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| isolate-ports | boolean | no | If true, sets the isolated property for all interfaces on the bridge of this VNet. |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| tag | integer | no | VLAN Tag (for VLAN or QinQ zones) or VXLAN VNI (for VXLAN or EVPN zones). |
| vlanaware | boolean | no | Allow VLANs to pass through this vnet. |
| zone | string | no | Name of the zone this VNet belongs to. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "description": "Require 'SDN.Allocate' permission on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update sdn vnet object configuration.",
  "method": "PUT",
  "name": "update",
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
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "description": "Require 'SDN.Allocate' permission on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
