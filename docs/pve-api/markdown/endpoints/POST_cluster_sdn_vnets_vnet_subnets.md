# POST /cluster/sdn/vnets/{vnet}/subnets

Create a new sdn subnet object.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vnet | string | yes | associated vnet |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| subnet | string | yes | The SDN subnet object identifier. |
| type | string | yes |  |
| dhcp-dns-server | string | no | IP address for the DNS server |
| dhcp-range | array | no | A list of DHCP ranges for this subnet |
| dnszoneprefix | string | no | dns domain zone prefix  ex: 'adm' -> <hostname>.adm.mydomain.com |
| gateway | string | no | Subnet Gateway: Will be assign on vnet for layer3 zones |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| snat | boolean | no | enable masquerade for this subnet if pve-firewall |

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
  "description": "Create a new sdn subnet object.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "dhcp-dns-server": {
        "description": "IP address for the DNS server",
        "format": "ip",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dhcp-range": {
        "description": "A list of DHCP ranges for this subnet",
        "items": {
          "format": "pve-sdn-dhcp-range",
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "dnszoneprefix": {
        "description": "dns domain zone prefix  ex: 'adm' -> <hostname>.adm.mydomain.com",
        "format": "dns-name",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "gateway": {
        "description": "Subnet Gateway: Will be assign on vnet for layer3 zones",
        "format": "ip",
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
      "snat": {
        "description": "enable masquerade for this subnet if pve-firewall",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "subnet": {
        "description": "The SDN subnet object identifier.",
        "format": "pve-sdn-subnet-id",
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "enum": [
          "subnet"
        ],
        "type": "string"
      },
      "vnet": {
        "description": "associated vnet",
        "optional": 0,
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
