# POST /nodes/{node}/qemu/{vmid}/dbus-vmstate

Control the dbus-vmstate helper for a given running VM.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| action | string | yes | Action to perform on the DBus VMState helper. |

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
      "VM.Migrate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Control the dbus-vmstate helper for a given running VM.",
  "method": "POST",
  "name": "dbus_vmstate",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "action": {
        "description": "Action to perform on the DBus VMState helper.",
        "enum": [
          "start",
          "stop"
        ],
        "optional": 0,
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
        "VM.Migrate"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
