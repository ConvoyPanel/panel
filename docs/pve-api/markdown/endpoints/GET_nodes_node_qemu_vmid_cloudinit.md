# GET /nodes/{node}/qemu/{vmid}/cloudinit

Get the cloudinit configuration with both current and pending values.

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
  "items": {
    "properties": {
      "delete": {
        "description": "Indicates a pending delete request if present and not 0. ",
        "maximum": 1,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "key": {
        "description": "Configuration option name.",
        "type": "string"
      },
      "pending": {
        "description": "The new pending value.",
        "optional": 1,
        "type": "string"
      },
      "value": {
        "description": "Value as it was used to generate the current cloudinit image.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
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
  "description": "Get the cloudinit configuration with both current and pending values.",
  "method": "GET",
  "name": "cloudinit_pending",
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
    "items": {
      "properties": {
        "delete": {
          "description": "Indicates a pending delete request if present and not 0. ",
          "maximum": 1,
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        },
        "key": {
          "description": "Configuration option name.",
          "type": "string"
        },
        "pending": {
          "description": "The new pending value.",
          "optional": 1,
          "type": "string"
        },
        "value": {
          "description": "Value as it was used to generate the current cloudinit image.",
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
