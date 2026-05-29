# GET /nodes/{node}/capabilities/qemu/cpu-flags

List of available VM-specific CPU flags. Returns an empty list for 'aarch64' as no VM-specific flags are defined for it yet.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| accel | string | no | Acceleration type to check node compatibility for. |
| arch | string | no | Virtual processor architecture. Defaults to the host architecture. |

## Returns

```json
{
  "items": {
    "properties": {
      "description": {
        "description": "Description of the CPU flag.",
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "Name of the CPU flag.",
        "type": "string"
      },
      "supported-on": {
        "description": "List of nodes supporting the CPU flag with the selected acceleration type (\"accel\").",
        "items": {
          "description": "The cluster node name.",
          "format": "pve-node",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List of available VM-specific CPU flags. Returns an empty list for 'aarch64' as no VM-specific flags are defined for it yet.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "accel": {
        "default": "kvm",
        "description": "Acceleration type to check node compatibility for.",
        "enum": [
          "kvm",
          "tcg"
        ],
        "optional": 1,
        "type": "string"
      },
      "arch": {
        "description": "Virtual processor architecture. Defaults to the host architecture.",
        "enum": [
          "x86_64",
          "aarch64"
        ],
        "optional": 1,
        "type": "string"
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
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "description": {
          "description": "Description of the CPU flag.",
          "optional": 1,
          "type": "string"
        },
        "name": {
          "description": "Name of the CPU flag.",
          "type": "string"
        },
        "supported-on": {
          "description": "List of nodes supporting the CPU flag with the selected acceleration type (\"accel\").",
          "items": {
            "description": "The cluster node name.",
            "format": "pve-node",
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
