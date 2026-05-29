# GET /nodes/{node}/lxc/{vmid}/firewall/log

Read firewall log

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| limit | integer | no |  |
| since | integer | no | Display log since this UNIX epoch. |
| start | integer | no |  |
| until | integer | no | Display log until this UNIX epoch. |

## Returns

```json
{
  "items": {
    "properties": {
      "n": {
        "description": "Line number",
        "type": "integer"
      },
      "t": {
        "description": "Line text",
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
      "VM.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read firewall log",
  "method": "GET",
  "name": "log",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "limit": {
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "since": {
        "description": "Display log since this UNIX epoch.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "start": {
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "until": {
        "description": "Display log until this UNIX epoch.",
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
        "VM.Console"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "n": {
          "description": "Line number",
          "type": "integer"
        },
        "t": {
          "description": "Line text",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
