# PUT /cluster/firewall/rules/{pos}

Modify rule data.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pos | integer | no | Update rule at position <pos>. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| action | string | no | Rule action ('ACCEPT', 'DROP', 'REJECT') or security group name. |
| comment | string | no | Descriptive comment. |
| delete | string | no | A list of settings you want to delete. |
| dest | string | no | Restrict packet destination address. This can refer to a single IP address, an IP set ('+ipsetname') or an IP alias definition. You can also specify an address range like '20.34.101.207-201.3.9.99', or a list of IP addresses and networks (entries are separated by comma). Please do not mix IPv4 and IPv6 addresses inside such lists. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| dport | string | no | Restrict TCP/UDP destination port. You can use service names or simple numbers (0-65535), as defined in '/etc/services'. Port ranges can be specified with '\d+:\d+', for example '80:85', and you can use comma separated list to match several ports or ranges. |
| enable | integer | no | Flag to enable/disable a rule. |
| icmp-type | string | no | Specify icmp-type. Only valid if proto equals 'icmp' or 'icmpv6'/'ipv6-icmp'. |
| iface | string | no | Network interface name. You have to use network configuration key names for VMs and containers ('net\d+'). Host related rules can use arbitrary strings. |
| log | string | no | Log level for firewall rule. |
| macro | string | no | Use predefined standard macro. |
| moveto | integer | no | Move rule to new position <moveto>. Other arguments are ignored. |
| proto | string | no | IP protocol. You can use protocol names ('tcp'/'udp') or simple numbers, as defined in '/etc/protocols'. |
| source | string | no | Restrict packet source address. This can refer to a single IP address, an IP set ('+ipsetname') or an IP alias definition. You can also specify an address range like '20.34.101.207-201.3.9.99', or a list of IP addresses and networks (entries are separated by comma). Please do not mix IPv4 and IPv6 addresses inside such lists. |
| sport | string | no | Restrict TCP/UDP source port. You can use service names or simple numbers (0-65535), as defined in '/etc/services'. Port ranges can be specified with '\d+:\d+', for example '80:85', and you can use comma separated list to match several ports or ranges. |
| type | string | no | Rule type. |

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
    "/",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Modify rule data.",
  "method": "PUT",
  "name": "update_rule",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "action": {
        "description": "Rule action ('ACCEPT', 'DROP', 'REJECT') or security group name.",
        "maxLength": 20,
        "minLength": 2,
        "optional": 1,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      },
      "comment": {
        "description": "Descriptive comment.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dest": {
        "description": "Restrict packet destination address. This can refer to a single IP address, an IP set ('+ipsetname') or an IP alias definition. You can also specify an address range like '20.34.101.207-201.3.9.99', or a list of IP addresses and networks (entries are separated by comma). Please do not mix IPv4 and IPv6 addresses inside such lists.",
        "format": "pve-fw-addr-spec",
        "maxLength": 512,
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
      "dport": {
        "description": "Restrict TCP/UDP destination port. You can use service names or simple numbers (0-65535), as defined in '/etc/services'. Port ranges can be specified with '\\d+:\\d+', for example '80:85', and you can use comma separated list to match several ports or ranges.",
        "format": "pve-fw-dport-spec",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "enable": {
        "description": "Flag to enable/disable a rule.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "icmp-type": {
        "description": "Specify icmp-type. Only valid if proto equals 'icmp' or 'icmpv6'/'ipv6-icmp'.",
        "format": "pve-fw-icmp-type-spec",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "iface": {
        "description": "Network interface name. You have to use network configuration key names for VMs and containers ('net\\d+'). Host related rules can use arbitrary strings.",
        "format": "pve-iface",
        "maxLength": 20,
        "minLength": 2,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "log": {
        "description": "Log level for firewall rule.",
        "enum": [
          "emerg",
          "alert",
          "crit",
          "err",
          "warning",
          "notice",
          "info",
          "debug",
          "nolog"
        ],
        "optional": 1,
        "type": "string"
      },
      "macro": {
        "description": "Use predefined standard macro.",
        "maxLength": 128,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "moveto": {
        "description": "Move rule to new position <moveto>. Other arguments are ignored.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "pos": {
        "description": "Update rule at position <pos>.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "proto": {
        "description": "IP protocol. You can use protocol names ('tcp'/'udp') or simple numbers, as defined in '/etc/protocols'.",
        "format": "pve-fw-protocol-spec",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "source": {
        "description": "Restrict packet source address. This can refer to a single IP address, an IP set ('+ipsetname') or an IP alias definition. You can also specify an address range like '20.34.101.207-201.3.9.99', or a list of IP addresses and networks (entries are separated by comma). Please do not mix IPv4 and IPv6 addresses inside such lists.",
        "format": "pve-fw-addr-spec",
        "maxLength": 512,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "sport": {
        "description": "Restrict TCP/UDP source port. You can use service names or simple numbers (0-65535), as defined in '/etc/services'. Port ranges can be specified with '\\d+:\\d+', for example '80:85', and you can use comma separated list to match several ports or ranges.",
        "format": "pve-fw-sport-spec",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "Rule type.",
        "enum": [
          "in",
          "out",
          "forward",
          "group"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": null,
  "returns": {
    "type": "null"
  }
}
```
