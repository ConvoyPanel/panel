# POST /cluster/sdn/vnets/{vnet}/ips

Create IP Mapping in a VNet

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
  "description": "Create IP Mapping in a VNet",
  "method": "POST",
  "name": "ipcreate",
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
