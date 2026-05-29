# GET /nodes/{node}/lxc/{vmid}/firewall/rules/{pos}

Get single rule data.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |
| pos | integer | no | Update rule at position <pos>. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "action": {
      "description": "Rule action ('ACCEPT', 'DROP', 'REJECT') or security group name",
      "type": "string"
    },
    "comment": {
      "description": "Descriptive comment",
      "optional": 1,
      "type": "string"
    },
    "dest": {
      "description": "Restrict packet destination address",
      "optional": 1,
      "type": "string"
    },
    "dport": {
      "description": "Restrict TCP/UDP destination port",
      "optional": 1,
      "type": "string"
    },
    "enable": {
      "description": "Flag to enable/disable a rule",
      "optional": 1,
      "type": "integer"
    },
    "icmp-type": {
      "description": "Specify icmp-type. Only valid if proto equals 'icmp' or 'icmpv6'/'ipv6-icmp'",
      "optional": 1,
      "type": "string"
    },
    "iface": {
      "description": "Network interface name. You have to use network configuration key names for VMs and containers",
      "optional": 1,
      "type": "string"
    },
    "ipversion": {
      "description": "IP version (4 or 6) - automatically determined from source/dest addresses",
      "optional": 1,
      "type": "integer"
    },
    "log": {
      "description": "Log level for firewall rule",
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
      "description": "Use predefined standard macro",
      "optional": 1,
      "type": "string"
    },
    "pos": {
      "description": "Rule position in the ruleset",
      "type": "integer"
    },
    "proto": {
      "description": "IP protocol. You can use protocol names ('tcp'/'udp') or simple numbers, as defined in '/etc/protocols'",
      "optional": 1,
      "type": "string"
    },
    "source": {
      "description": "Restrict packet source address",
      "optional": 1,
      "type": "string"
    },
    "sport": {
      "description": "Restrict TCP/UDP source port",
      "optional": 1,
      "type": "string"
    },
    "type": {
      "description": "Rule type",
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get single rule data.",
  "method": "GET",
  "name": "get_rule",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pos": {
        "description": "Update rule at position <pos>.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Audit"
      ]
    ]
  },
  "proxyto": null,
  "returns": {
    "properties": {
      "action": {
        "description": "Rule action ('ACCEPT', 'DROP', 'REJECT') or security group name",
        "type": "string"
      },
      "comment": {
        "description": "Descriptive comment",
        "optional": 1,
        "type": "string"
      },
      "dest": {
        "description": "Restrict packet destination address",
        "optional": 1,
        "type": "string"
      },
      "dport": {
        "description": "Restrict TCP/UDP destination port",
        "optional": 1,
        "type": "string"
      },
      "enable": {
        "description": "Flag to enable/disable a rule",
        "optional": 1,
        "type": "integer"
      },
      "icmp-type": {
        "description": "Specify icmp-type. Only valid if proto equals 'icmp' or 'icmpv6'/'ipv6-icmp'",
        "optional": 1,
        "type": "string"
      },
      "iface": {
        "description": "Network interface name. You have to use network configuration key names for VMs and containers",
        "optional": 1,
        "type": "string"
      },
      "ipversion": {
        "description": "IP version (4 or 6) - automatically determined from source/dest addresses",
        "optional": 1,
        "type": "integer"
      },
      "log": {
        "description": "Log level for firewall rule",
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
        "description": "Use predefined standard macro",
        "optional": 1,
        "type": "string"
      },
      "pos": {
        "description": "Rule position in the ruleset",
        "type": "integer"
      },
      "proto": {
        "description": "IP protocol. You can use protocol names ('tcp'/'udp') or simple numbers, as defined in '/etc/protocols'",
        "optional": 1,
        "type": "string"
      },
      "source": {
        "description": "Restrict packet source address",
        "optional": 1,
        "type": "string"
      },
      "sport": {
        "description": "Restrict TCP/UDP source port",
        "optional": 1,
        "type": "string"
      },
      "type": {
        "description": "Rule type",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
