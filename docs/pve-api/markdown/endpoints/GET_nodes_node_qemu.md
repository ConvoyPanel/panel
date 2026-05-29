# GET /nodes/{node}/qemu

Virtual machine index (per node).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| full | boolean | no | Determine the full status of active VMs. |

## Returns

```json
{
  "items": {
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
      "lock": {
        "description": "The current config lock, if any.",
        "optional": 1,
        "type": "string"
      },
      "maxdisk": {
        "description": "Root disk size in bytes.",
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
      "mem": {
        "description": "Currently used memory in bytes. Does not take into account kernel same-page merging (KSM). Uses information from ballooning when available.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "memhost": {
        "description": "Current memory usage on the host. Does not take into account kernel same-page merging (KSM).",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "name": {
        "description": "VM (host)name.",
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
      "pid": {
        "description": "PID of the QEMU process, if the VM is running.",
        "optional": 1,
        "type": "integer"
      },
      "pressurecpufull": {
        "description": "CPU Full pressure stall average over the last 10 seconds.",
        "optional": 1,
        "type": "number"
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
      "qmpstatus": {
        "description": "VM run state from the 'query-status' QMP monitor command.",
        "optional": 1,
        "type": "string"
      },
      "running-machine": {
        "description": "The currently running machine type (if running).",
        "optional": 1,
        "type": "string"
      },
      "running-qemu": {
        "description": "The QEMU version the VM is currently using (if running).",
        "optional": 1,
        "type": "string"
      },
      "serial": {
        "description": "Guest has serial device configured.",
        "optional": 1,
        "type": "boolean"
      },
      "status": {
        "description": "QEMU process status.",
        "enum": [
          "stopped",
          "running"
        ],
        "type": "string"
      },
      "tags": {
        "description": "The current configured tags, if any",
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
  },
  "links": [
    {
      "href": "{vmid}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list VMs where you have VM.Audit permissions on /vms/<vmid>.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Virtual machine index (per node).",
  "method": "GET",
  "name": "vmlist",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "full": {
        "description": "Determine the full status of active VMs.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Only list VMs where you have VM.Audit permissions on /vms/<vmid>.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
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
        "lock": {
          "description": "The current config lock, if any.",
          "optional": 1,
          "type": "string"
        },
        "maxdisk": {
          "description": "Root disk size in bytes.",
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
        "mem": {
          "description": "Currently used memory in bytes. Does not take into account kernel same-page merging (KSM). Uses information from ballooning when available.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "memhost": {
          "description": "Current memory usage on the host. Does not take into account kernel same-page merging (KSM).",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "name": {
          "description": "VM (host)name.",
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
        "pid": {
          "description": "PID of the QEMU process, if the VM is running.",
          "optional": 1,
          "type": "integer"
        },
        "pressurecpufull": {
          "description": "CPU Full pressure stall average over the last 10 seconds.",
          "optional": 1,
          "type": "number"
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
        "qmpstatus": {
          "description": "VM run state from the 'query-status' QMP monitor command.",
          "optional": 1,
          "type": "string"
        },
        "running-machine": {
          "description": "The currently running machine type (if running).",
          "optional": 1,
          "type": "string"
        },
        "running-qemu": {
          "description": "The QEMU version the VM is currently using (if running).",
          "optional": 1,
          "type": "string"
        },
        "serial": {
          "description": "Guest has serial device configured.",
          "optional": 1,
          "type": "boolean"
        },
        "status": {
          "description": "QEMU process status.",
          "enum": [
            "stopped",
            "running"
          ],
          "type": "string"
        },
        "tags": {
          "description": "The current configured tags, if any",
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
    },
    "links": [
      {
        "href": "{vmid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
