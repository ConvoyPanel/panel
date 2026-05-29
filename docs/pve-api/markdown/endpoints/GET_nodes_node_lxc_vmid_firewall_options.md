# GET /nodes/{node}/lxc/{vmid}/firewall/options

Get VM firewall options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "dhcp": {
      "default": 0,
      "description": "Enable DHCP.",
      "optional": 1,
      "type": "boolean"
    },
    "enable": {
      "default": 0,
      "description": "Enable/disable firewall rules.",
      "optional": 1,
      "type": "boolean"
    },
    "ipfilter": {
      "description": "Enable default IP filters. This is equivalent to adding an empty ipfilter-net<id> ipset for every interface. Such ipsets implicitly contain sane default restrictions such as restricting IPv6 link local addresses to the one derived from the interface's MAC address. For containers the configured IP addresses will be implicitly added.",
      "optional": 1,
      "type": "boolean"
    },
    "log_level_in": {
      "description": "Log level for incoming traffic.",
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
    "log_level_out": {
      "description": "Log level for outgoing traffic.",
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
    "macfilter": {
      "default": 1,
      "description": "Enable/disable MAC address filter.",
      "optional": 1,
      "type": "boolean"
    },
    "ndp": {
      "default": 1,
      "description": "Enable NDP (Neighbor Discovery Protocol).",
      "optional": 1,
      "type": "boolean"
    },
    "policy_in": {
      "description": "Input policy.",
      "enum": [
        "ACCEPT",
        "REJECT",
        "DROP"
      ],
      "optional": 1,
      "type": "string"
    },
    "policy_out": {
      "description": "Output policy.",
      "enum": [
        "ACCEPT",
        "REJECT",
        "DROP"
      ],
      "optional": 1,
      "type": "string"
    },
    "radv": {
      "description": "Allow sending Router Advertisement.",
      "optional": 1,
      "type": "boolean"
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
  "description": "Get VM firewall options.",
  "method": "GET",
  "name": "get_options",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
  "proxyto": "node",
  "returns": {
    "properties": {
      "dhcp": {
        "default": 0,
        "description": "Enable DHCP.",
        "optional": 1,
        "type": "boolean"
      },
      "enable": {
        "default": 0,
        "description": "Enable/disable firewall rules.",
        "optional": 1,
        "type": "boolean"
      },
      "ipfilter": {
        "description": "Enable default IP filters. This is equivalent to adding an empty ipfilter-net<id> ipset for every interface. Such ipsets implicitly contain sane default restrictions such as restricting IPv6 link local addresses to the one derived from the interface's MAC address. For containers the configured IP addresses will be implicitly added.",
        "optional": 1,
        "type": "boolean"
      },
      "log_level_in": {
        "description": "Log level for incoming traffic.",
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
      "log_level_out": {
        "description": "Log level for outgoing traffic.",
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
      "macfilter": {
        "default": 1,
        "description": "Enable/disable MAC address filter.",
        "optional": 1,
        "type": "boolean"
      },
      "ndp": {
        "default": 1,
        "description": "Enable NDP (Neighbor Discovery Protocol).",
        "optional": 1,
        "type": "boolean"
      },
      "policy_in": {
        "description": "Input policy.",
        "enum": [
          "ACCEPT",
          "REJECT",
          "DROP"
        ],
        "optional": 1,
        "type": "string"
      },
      "policy_out": {
        "description": "Output policy.",
        "enum": [
          "ACCEPT",
          "REJECT",
          "DROP"
        ],
        "optional": 1,
        "type": "string"
      },
      "radv": {
        "description": "Allow sending Router Advertisement.",
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
