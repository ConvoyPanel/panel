# PUT /cluster/sdn/vnets/{vnet}/subnets/{subnet}

Update sdn subnet object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| subnet | string | yes | The SDN subnet object identifier. |
| vnet | string | no | associated vnet |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| dhcp-dns-server | string | no | IP address for the DNS server |
| dhcp-range | array | no | A list of DHCP ranges for this subnet |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
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
  "description": "Update sdn subnet object configuration.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
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
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
      "vnet": {
        "description": "associated vnet",
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
