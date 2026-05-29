# POST /nodes/{node}/qemu/{vmid}/agent/exec

Executes the given command in the vm via the guest-agent and returns an object with the pid.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| command | array | yes | The command as a list of program + arguments. |
| input-data | string | no | Data to pass as 'input-data' to the guest. Usually treated as STDIN to 'command'. |

## Returns

```json
{
  "properties": {
    "pid": {
      "description": "The PID of the process started by the guest-agent.",
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
  "description": "Executes the given command in the vm via the guest-agent and returns an object with the pid.",
  "method": "POST",
  "name": "exec",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "command": {
        "description": "The command as a list of program + arguments.",
        "items": {
          "description": "A single part of the program + arguments.",
          "type": "string"
        },
        "type": "array",
        "typetext": "<array>"
      },
      "input-data": {
        "description": "Data to pass as 'input-data' to the guest. Usually treated as STDIN to 'command'.",
        "maxLength": 65536,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
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
        "VM.GuestAgent.Unrestricted"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "pid": {
        "description": "The PID of the process started by the guest-agent.",
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
