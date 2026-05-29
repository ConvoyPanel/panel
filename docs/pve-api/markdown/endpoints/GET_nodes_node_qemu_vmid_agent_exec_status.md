# GET /nodes/{node}/qemu/{vmid}/agent/exec-status

Gets the status of the given pid started by the guest-agent

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pid | integer | yes | The PID to query |

## Returns

```json
{
  "properties": {
    "err-data": {
      "description": "stderr of the process",
      "optional": 1,
      "type": "string"
    },
    "err-truncated": {
      "description": "true if stderr was not fully captured",
      "optional": 1,
      "type": "boolean"
    },
    "exitcode": {
      "description": "process exit code if it was normally terminated.",
      "optional": 1,
      "type": "integer"
    },
    "exited": {
      "description": "Tells if the given command has exited yet.",
      "type": "boolean"
    },
    "out-data": {
      "description": "stdout of the process",
      "optional": 1,
      "type": "string"
    },
    "out-truncated": {
      "description": "true if stdout was not fully captured",
      "optional": 1,
      "type": "boolean"
    },
    "signal": {
      "description": "signal number or exception code if the process was abnormally terminated.",
      "optional": 1,
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
      "VM.GuestAgent.Unrestricted"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Gets the status of the given pid started by the guest-agent",
  "method": "GET",
  "name": "exec-status",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pid": {
        "description": "The PID to query",
        "type": "integer",
        "typetext": "<integer>"
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
        "VM.GuestAgent.Unrestricted"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "err-data": {
        "description": "stderr of the process",
        "optional": 1,
        "type": "string"
      },
      "err-truncated": {
        "description": "true if stderr was not fully captured",
        "optional": 1,
        "type": "boolean"
      },
      "exitcode": {
        "description": "process exit code if it was normally terminated.",
        "optional": 1,
        "type": "integer"
      },
      "exited": {
        "description": "Tells if the given command has exited yet.",
        "type": "boolean"
      },
      "out-data": {
        "description": "stdout of the process",
        "optional": 1,
        "type": "string"
      },
      "out-truncated": {
        "description": "true if stdout was not fully captured",
        "optional": 1,
        "type": "boolean"
      },
      "signal": {
        "description": "signal number or exception code if the process was abnormally terminated.",
        "optional": 1,
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
