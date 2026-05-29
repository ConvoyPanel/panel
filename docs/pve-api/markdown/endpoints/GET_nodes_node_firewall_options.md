# GET /nodes/{node}/firewall/options

Get host firewall options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "enable": {
      "default": 1,
      "description": "Enable host firewall rules.",
      "optional": 1,
      "type": "boolean"
    },
    "log_level_forward": {
      "description": "Log level for forwarded traffic.",
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
    "log_nf_conntrack": {
      "default": 0,
      "description": "Enable logging of conntrack information.",
      "optional": 1,
      "type": "boolean"
    },
    "ndp": {
      "default": 1,
      "description": "Enable NDP (Neighbor Discovery Protocol).",
      "optional": 1,
      "type": "boolean"
    },
    "nf_conntrack_allow_invalid": {
      "default": 0,
      "description": "Allow invalid packets on connection tracking.",
      "optional": 1,
      "type": "boolean"
    },
    "nf_conntrack_helpers": {
      "default": "",
      "description": "Enable conntrack helpers for specific protocols. Supported protocols: amanda, ftp, irc, netbios-ns, pptp, sane, sip, snmp, tftp",
      "format": "pve-fw-conntrack-helper",
      "optional": 1,
      "type": "string"
    },
    "nf_conntrack_max": {
      "default": 262144,
      "description": "Maximum number of tracked connections.",
      "minimum": 32768,
      "optional": 1,
      "type": "integer"
    },
    "nf_conntrack_tcp_timeout_established": {
      "default": 432000,
      "description": "Conntrack established timeout.",
      "minimum": 7875,
      "optional": 1,
      "type": "integer"
    },
    "nf_conntrack_tcp_timeout_syn_recv": {
      "default": 60,
      "description": "Conntrack syn recv timeout.",
      "maximum": 60,
      "minimum": 30,
      "optional": 1,
      "type": "integer"
    },
    "nftables": {
      "default": 0,
      "description": "Enable nftables based firewall (tech preview)",
      "optional": 1,
      "type": "boolean"
    },
    "nosmurfs": {
      "description": "Enable SMURFS filter.",
      "optional": 1,
      "type": "boolean"
    },
    "protection_synflood": {
      "default": 0,
      "description": "Enable synflood protection",
      "optional": 1,
      "type": "boolean"
    },
    "protection_synflood_burst": {
      "default": 1000,
      "description": "Synflood protection rate burst by ip src.",
      "optional": 1,
      "type": "integer"
    },
    "protection_synflood_rate": {
      "default": 200,
      "description": "Synflood protection rate syn/sec by ip src.",
      "optional": 1,
      "type": "integer"
    },
    "smurf_log_level": {
      "description": "Log level for SMURFS filter.",
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
    "tcp_flags_log_level": {
      "description": "Log level for illegal tcp flags filter.",
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
    "tcpflags": {
      "default": 0,
      "description": "Filter illegal combinations of TCP flags.",
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
    "/nodes/{node}",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get host firewall options.",
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
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "properties": {
      "enable": {
        "default": 1,
        "description": "Enable host firewall rules.",
        "optional": 1,
        "type": "boolean"
      },
      "log_level_forward": {
        "description": "Log level for forwarded traffic.",
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
      "log_nf_conntrack": {
        "default": 0,
        "description": "Enable logging of conntrack information.",
        "optional": 1,
        "type": "boolean"
      },
      "ndp": {
        "default": 1,
        "description": "Enable NDP (Neighbor Discovery Protocol).",
        "optional": 1,
        "type": "boolean"
      },
      "nf_conntrack_allow_invalid": {
        "default": 0,
        "description": "Allow invalid packets on connection tracking.",
        "optional": 1,
        "type": "boolean"
      },
      "nf_conntrack_helpers": {
        "default": "",
        "description": "Enable conntrack helpers for specific protocols. Supported protocols: amanda, ftp, irc, netbios-ns, pptp, sane, sip, snmp, tftp",
        "format": "pve-fw-conntrack-helper",
        "optional": 1,
        "type": "string"
      },
      "nf_conntrack_max": {
        "default": 262144,
        "description": "Maximum number of tracked connections.",
        "minimum": 32768,
        "optional": 1,
        "type": "integer"
      },
      "nf_conntrack_tcp_timeout_established": {
        "default": 432000,
        "description": "Conntrack established timeout.",
        "minimum": 7875,
        "optional": 1,
        "type": "integer"
      },
      "nf_conntrack_tcp_timeout_syn_recv": {
        "default": 60,
        "description": "Conntrack syn recv timeout.",
        "maximum": 60,
        "minimum": 30,
        "optional": 1,
        "type": "integer"
      },
      "nftables": {
        "default": 0,
        "description": "Enable nftables based firewall (tech preview)",
        "optional": 1,
        "type": "boolean"
      },
      "nosmurfs": {
        "description": "Enable SMURFS filter.",
        "optional": 1,
        "type": "boolean"
      },
      "protection_synflood": {
        "default": 0,
        "description": "Enable synflood protection",
        "optional": 1,
        "type": "boolean"
      },
      "protection_synflood_burst": {
        "default": 1000,
        "description": "Synflood protection rate burst by ip src.",
        "optional": 1,
        "type": "integer"
      },
      "protection_synflood_rate": {
        "default": 200,
        "description": "Synflood protection rate syn/sec by ip src.",
        "optional": 1,
        "type": "integer"
      },
      "smurf_log_level": {
        "description": "Log level for SMURFS filter.",
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
      "tcp_flags_log_level": {
        "description": "Log level for illegal tcp flags filter.",
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
      "tcpflags": {
        "default": 0,
        "description": "Filter illegal combinations of TCP flags.",
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
