# GET /nodes/{node}/lxc/{vmid}/status/current

Get virtual machine status.

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
    "cpu": {
      "description": "Current CPU usage.",
      "optional": 1,
      "type": "number"
    },
    "cpus": {
      "description": "Maximum usable CPUs.",
      "optional": 1,
      "type": "number"
    },
    "disk": {
      "description": "Root disk image space-usage in bytes.",
      "minimum": 0,
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "diskread": {
      "description": "The amount of bytes the guest read from it's block devices since the guest was started. (Note: This info is not available for all storage types.)",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "diskwrite": {
      "description": "The amount of bytes the guest wrote from it's block devices since the guest was started. (Note: This info is not available for all storage types.)",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "ha": {
      "description": "HA manager service status.",
      "type": "object"
    },
    "lock": {
      "description": "The current config lock, if any.",
      "optional": 1,
      "type": "string"
    },
    "maxdisk": {
      "description": "Root disk image size in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "maxmem": {
      "description": "Maximum memory in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "maxswap": {
      "description": "Maximum SWAP memory in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "mem": {
      "description": "Currently used memory in bytes.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "name": {
      "description": "Container name.",
      "optional": 1,
      "type": "string"
    },
    "netin": {
      "description": "The amount of traffic in bytes that was sent to the guest over the network since it was started.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "netout": {
      "description": "The amount of traffic in bytes that was sent from the guest over the network since it was started.",
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    },
    "pressurecpusome": {
      "description": "CPU Some pressure stall average over the last 10 seconds.",
      "optional": 1,
      "type": "number"
    },
    "pressureiofull": {
      "description": "IO Full pressure stall average over the last 10 seconds.",
      "optional": 1,
      "type": "number"
    },
    "pressureiosome": {
      "description": "IO Some pressure stall average over the last 10 seconds.",
      "optional": 1,
      "type": "number"
    },
    "pressurememoryfull": {
      "description": "Memory Full pressure stall average over the last 10 seconds.",
      "optional": 1,
      "type": "number"
    },
    "pressurememorysome": {
      "description": "Memory Some pressure stall average over the last 10 seconds.",
      "optional": 1,
      "type": "number"
    },
    "status": {
      "description": "LXC Container status.",
      "enum": [
        "stopped",
        "running"
      ],
      "type": "string"
    },
    "tags": {
      "description": "The current configured tags, if any.",
      "optional": 1,
      "type": "string"
    },
    "template": {
      "default": 0,
      "description": "Determines if the guest is a template.",
      "optional": 1,
      "type": "boolean"
    },
    "uptime": {
      "description": "Uptime in seconds.",
      "optional": 1,
      "renderer": "duration",
      "type": "integer"
    },
    "vmid": {
      "description": "The (unique) ID of the VM.",
      "format": "pve-vmid",
      "maximum": 999999999,
      "minimum": 100,
      "type": "integer"
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
  "description": "Get virtual machine status.",
  "method": "GET",
  "name": "vm_status",
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
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "cpu": {
        "description": "Current CPU usage.",
        "optional": 1,
        "type": "number"
      },
      "cpus": {
        "description": "Maximum usable CPUs.",
        "optional": 1,
        "type": "number"
      },
      "disk": {
        "description": "Root disk image space-usage in bytes.",
        "minimum": 0,
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "diskread": {
        "description": "The amount of bytes the guest read from it's block devices since the guest was started. (Note: This info is not available for all storage types.)",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "diskwrite": {
        "description": "The amount of bytes the guest wrote from it's block devices since the guest was started. (Note: This info is not available for all storage types.)",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "ha": {
        "description": "HA manager service status.",
        "type": "object"
      },
      "lock": {
        "description": "The current config lock, if any.",
        "optional": 1,
        "type": "string"
      },
      "maxdisk": {
        "description": "Root disk image size in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "maxmem": {
        "description": "Maximum memory in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "maxswap": {
        "description": "Maximum SWAP memory in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "mem": {
        "description": "Currently used memory in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "name": {
        "description": "Container name.",
        "optional": 1,
        "type": "string"
      },
      "netin": {
        "description": "The amount of traffic in bytes that was sent to the guest over the network since it was started.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "netout": {
        "description": "The amount of traffic in bytes that was sent from the guest over the network since it was started.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "pressurecpusome": {
        "description": "CPU Some pressure stall average over the last 10 seconds.",
        "optional": 1,
        "type": "number"
      },
      "pressureiofull": {
        "description": "IO Full pressure stall average over the last 10 seconds.",
        "optional": 1,
        "type": "number"
      },
      "pressureiosome": {
        "description": "IO Some pressure stall average over the last 10 seconds.",
        "optional": 1,
        "type": "number"
      },
      "pressurememoryfull": {
        "description": "Memory Full pressure stall average over the last 10 seconds.",
        "optional": 1,
        "type": "number"
      },
      "pressurememorysome": {
        "description": "Memory Some pressure stall average over the last 10 seconds.",
        "optional": 1,
        "type": "number"
      },
      "status": {
        "description": "LXC Container status.",
        "enum": [
          "stopped",
          "running"
        ],
        "type": "string"
      },
      "tags": {
        "description": "The current configured tags, if any.",
        "optional": 1,
        "type": "string"
      },
      "template": {
        "default": 0,
        "description": "Determines if the guest is a template.",
        "optional": 1,
        "type": "boolean"
      },
      "uptime": {
        "description": "Uptime in seconds.",
        "optional": 1,
        "renderer": "duration",
        "type": "integer"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
