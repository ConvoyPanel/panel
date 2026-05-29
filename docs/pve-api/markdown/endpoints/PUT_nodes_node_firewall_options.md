# PUT /nodes/{node}/firewall/options

Set Firewall options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| enable | boolean | no | Enable host firewall rules. |
| log_level_forward | string | no | Log level for forwarded traffic. |
| log_level_in | string | no | Log level for incoming traffic. |
| log_level_out | string | no | Log level for outgoing traffic. |
| log_nf_conntrack | boolean | no | Enable logging of conntrack information. |
| ndp | boolean | no | Enable NDP (Neighbor Discovery Protocol). |
| nf_conntrack_allow_invalid | boolean | no | Allow invalid packets on connection tracking. |
| nf_conntrack_helpers | string | no | Enable conntrack helpers for specific protocols. Supported protocols: amanda, ftp, irc, netbios-ns, pptp, sane, sip, snmp, tftp |
| nf_conntrack_max | integer | no | Maximum number of tracked connections. |
| nf_conntrack_tcp_timeout_established | integer | no | Conntrack established timeout. |
| nf_conntrack_tcp_timeout_syn_recv | integer | no | Conntrack syn recv timeout. |
| nftables | boolean | no | Enable nftables based firewall (tech preview) |
| nosmurfs | boolean | no | Enable SMURFS filter. |
| protection_synflood | boolean | no | Enable synflood protection |
| protection_synflood_burst | integer | no | Synflood protection rate burst by ip src. |
| protection_synflood_rate | integer | no | Synflood protection rate syn/sec by ip src. |
| smurf_log_level | string | no | Log level for SMURFS filter. |
| tcp_flags_log_level | string | no | Log level for illegal tcp flags filter. |
| tcpflags | boolean | no | Filter illegal combinations of TCP flags. |

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
    "/nodes/{node}",
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
  "description": "Set Firewall options.",
  "method": "PUT",
  "name": "set_options",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
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
      "enable": {
        "default": 1,
        "description": "Enable host firewall rules.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "ndp": {
        "default": 1,
        "description": "Enable NDP (Neighbor Discovery Protocol).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "nf_conntrack_allow_invalid": {
        "default": 0,
        "description": "Allow invalid packets on connection tracking.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "nf_conntrack_helpers": {
        "default": "",
        "description": "Enable conntrack helpers for specific protocols. Supported protocols: amanda, ftp, irc, netbios-ns, pptp, sane, sip, snmp, tftp",
        "format": "pve-fw-conntrack-helper",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "nf_conntrack_max": {
        "default": 262144,
        "description": "Maximum number of tracked connections.",
        "minimum": 32768,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (32768 - N)"
      },
      "nf_conntrack_tcp_timeout_established": {
        "default": 432000,
        "description": "Conntrack established timeout.",
        "minimum": 7875,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (7875 - N)"
      },
      "nf_conntrack_tcp_timeout_syn_recv": {
        "default": 60,
        "description": "Conntrack syn recv timeout.",
        "maximum": 60,
        "minimum": 30,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (30 - 60)"
      },
      "nftables": {
        "default": 0,
        "description": "Enable nftables based firewall (tech preview)",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "nosmurfs": {
        "description": "Enable SMURFS filter.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "protection_synflood": {
        "default": 0,
        "description": "Enable synflood protection",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "protection_synflood_burst": {
        "default": 1000,
        "description": "Synflood protection rate burst by ip src.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "protection_synflood_rate": {
        "default": 200,
        "description": "Synflood protection rate syn/sec by ip src.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
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
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
