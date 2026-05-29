# PUT /cluster/sdn/vnets/{vnet}/ips

Update IP Mapping in a VNet

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | The SDN vnet object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| ip | string | yes | The IP address to associate with the given MAC address |
| zone | string | yes | The SDN zone object identifier. |
| mac | string | no | Unicast MAC address. |
| vmid | integer | no | The (unique) ID of the VM. |

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
    "/sdn/zones/{zone}/{vnet}",
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
  "description": "Update IP Mapping in a VNet",
  "method": "PUT",
  "name": "ipupdate",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "ip": {
        "description": "The IP address to associate with the given MAC address",
        "format": "ip",
        "type": "string",
        "typetext": "<string>"
      },
      "mac": {
        "description": "Unicast MAC address.",
        "format": "mac-addr",
        "format_description": "XX:XX:XX:XX:XX:XX",
        "optional": 1,
        "type": "string",
        "typetext": "<XX:XX:XX:XX:XX:XX>",
        "verbose_description": "A common MAC address with the I/G (Individual/Group) bit not set."
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "vnet": {
        "description": "The SDN vnet object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      },
      "zone": {
        "description": "The SDN zone object identifier.",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/zones/{zone}/{vnet}",
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
