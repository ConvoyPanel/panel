# GET /nodes/{node}/capabilities/qemu/machines

Get available QEMU/KVM machine types.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| arch | string | no | Virtual processor architecture. Defaults to the host architecture. |

## Returns

```json
{
  "items": {
    "additionalProperties": 1,
    "properties": {
      "changes": {
        "description": "Notable changes of a version, currently only set for +pveX versions.",
        "optional": 1,
        "type": "string"
      },
      "id": {
        "description": "Full name of machine type and version.",
        "type": "string"
      },
      "type": {
        "description": "The machine type.",
        "enum": [
          "q35",
          "i440fx"
        ],
        "type": "string"
      },
      "version": {
        "description": "The machine version.",
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get available QEMU/KVM machine types.",
  "method": "GET",
  "name": "types",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
  "proxyto": "node",
  "returns": {
    "items": {
      "additionalProperties": 1,
      "properties": {
        "changes": {
          "description": "Notable changes of a version, currently only set for +pveX versions.",
          "optional": 1,
          "type": "string"
        },
        "id": {
          "description": "Full name of machine type and version.",
          "type": "string"
        },
        "type": {
          "description": "The machine type.",
          "enum": [
            "q35",
            "i440fx"
          ],
          "type": "string"
        },
        "version": {
          "description": "The machine version.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
