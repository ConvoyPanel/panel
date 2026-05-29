# POST /nodes/{node}/qemu/{vmid}/termproxy

Creates a TCP proxy connections.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| serial | string | no | opens a serial terminal (defaults to display) |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "port": {
      "type": "integer"
    },
    "ticket": {
      "type": "string"
    },
    "upid": {
      "type": "string"
    },
    "user": {
      "type": "string"
    }
  }
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Creates a TCP proxy connections.",
  "method": "POST",
  "name": "termproxy",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "serial": {
        "description": "opens a serial terminal (defaults to display)",
        "enum": [
          "serial0",
          "serial1",
          "serial2",
          "serial3"
        ],
        "optional": 1,
        "type": "string"
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
        "VM.Console"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "port": {
        "type": "integer"
      },
      "ticket": {
        "type": "string"
      },
      "upid": {
        "type": "string"
      },
      "user": {
        "type": "string"
      }
    }
  }
}
```
