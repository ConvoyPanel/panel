# PUT /nodes/{node}/qemu/{vmid}/firewall/options

Set Firewall options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| dhcp | boolean | no | Enable DHCP. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| enable | boolean | no | Enable/disable firewall rules. |
| ipfilter | boolean | no | Enable default IP filters. This is equivalent to adding an empty ipfilter-net<id> ipset for every interface. Such ipsets implicitly contain sane default restrictions such as restricting IPv6 link local addresses to the one derived from the interface's MAC address. For containers the configured IP addresses will be implicitly added. |
| log_level_in | string | no | Log level for incoming traffic. |
| log_level_out | string | no | Log level for outgoing traffic. |
| macfilter | boolean | no | Enable/disable MAC address filter. |
| ndp | boolean | no | Enable NDP (Neighbor Discovery Protocol). |
| policy_in | string | no | Input policy. |
| policy_out | string | no | Output policy. |
| radv | boolean | no | Allow sending Router Advertisement. |

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
    "/vms/{vmid}",
    [
      "VM.Config.Network"
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
      "dhcp": {
        "default": 0,
        "description": "Enable DHCP.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "enable": {
        "default": 0,
        "description": "Enable/disable firewall rules.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "ipfilter": {
        "description": "Enable default IP filters. This is equivalent to adding an empty ipfilter-net<id> ipset for every interface. Such ipsets implicitly contain sane default restrictions such as restricting IPv6 link local addresses to the one derived from the interface's MAC address. For containers the configured IP addresses will be implicitly added.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
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
        "type": "boolean",
        "typetext": "<boolean>"
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
        "VM.Config.Network"
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
