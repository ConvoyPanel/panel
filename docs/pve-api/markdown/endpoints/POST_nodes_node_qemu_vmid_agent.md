# POST /nodes/{node}/qemu/{vmid}/agent

Execute QEMU Guest Agent commands.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| command | string | yes | The QGA command. |

## Returns

```json
{
  "description": "Returns an object with a single `result` property.",
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
      "VM.GuestAgent.Unrestricted",
      "VM.GuestAgent.Unrestricted"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Execute QEMU Guest Agent commands.",
  "method": "POST",
  "name": "agent",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "command": {
        "description": "The QGA command.",
        "enum": [
          "fsfreeze-freeze",
          "fsfreeze-status",
          "fsfreeze-thaw",
          "fstrim",
          "get-fsinfo",
          "get-host-name",
          "get-memory-block-info",
          "get-memory-blocks",
          "get-osinfo",
          "get-time",
          "get-timezone",
          "get-users",
          "get-vcpus",
          "info",
          "network-get-interfaces",
          "ping",
          "shutdown",
          "suspend-disk",
          "suspend-hybrid",
          "suspend-ram"
        ],
        "type": "string"
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
        "VM.GuestAgent.Unrestricted",
        "VM.GuestAgent.Unrestricted"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "Returns an object with a single `result` property.",
    "type": "object"
  }
}
```
