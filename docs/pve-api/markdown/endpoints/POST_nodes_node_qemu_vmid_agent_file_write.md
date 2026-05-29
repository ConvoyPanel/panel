# POST /nodes/{node}/qemu/{vmid}/agent/file-write

Writes the given file via guest agent.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| content | string | yes | The content to write into the file. |
| file | string | yes | The path to the file. |
| encode | boolean | no | If set, the content will be encoded as base64 (required by QEMU).Otherwise the content needs to be encoded beforehand - defaults to true. |

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
      "VM.GuestAgent.FileWrite",
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
  "description": "Writes the given file via guest agent.",
  "method": "POST",
  "name": "file-write",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "content": {
        "description": "The content to write into the file.",
        "maxLength": 61440,
        "type": "string",
        "typetext": "<string>"
      },
      "encode": {
        "default": 1,
        "description": "If set, the content will be encoded as base64 (required by QEMU).Otherwise the content needs to be encoded beforehand - defaults to true.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "file": {
        "description": "The path to the file.",
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
        "VM.GuestAgent.FileWrite",
        "VM.GuestAgent.Unrestricted"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
