# GET /nodes/{node}/qemu/{vmid}/agent/file-read

Reads the given file via guest agent. Is limited to 16777216 bytes.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| file | string | yes | The path to the file |
| count | integer | no | Number of bytes to read. |
| decode | boolean | no | Data received from the QEMU Guest-Agent is base64 encoded. If this is set to true, the data is decoded. Otherwise the content is forwarded with base64 encoding. Defaults to true. |
| offset | integer | no | Offset to start reading at |

## Returns

```json
{
  "description": "Returns an object with a `content` property.",
  "properties": {
    "content": {
      "description": "The content of the file, maximum 16777216",
      "type": "string"
    },
    "truncated": {
      "description": "If set to 1, the read did not reach the end of the file.",
      "optional": 1,
      "type": "boolean"
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
      "VM.GuestAgent.FileRead",
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
  "description": "Reads the given file via guest agent. Is limited to 16777216 bytes.",
  "method": "GET",
  "name": "file-read",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "count": {
        "default": "16777216",
        "description": "Number of bytes to read.",
        "maximum": "16777216",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 16777216)"
      },
      "decode": {
        "default": 1,
        "description": "Data received from the QEMU Guest-Agent is base64 encoded. If this is set to true, the data is decoded. Otherwise the content is forwarded with base64 encoding. Defaults to true.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "file": {
        "description": "The path to the file",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "offset": {
        "default": 0,
        "description": "Offset to start reading at",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
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
        "VM.GuestAgent.FileRead",
        "VM.GuestAgent.Unrestricted"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "Returns an object with a `content` property.",
    "properties": {
      "content": {
        "description": "The content of the file, maximum 16777216",
        "type": "string"
      },
      "truncated": {
        "description": "If set to 1, the read did not reach the end of the file.",
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
