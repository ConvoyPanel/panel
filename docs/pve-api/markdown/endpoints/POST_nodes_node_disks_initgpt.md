# POST /nodes/{node}/disks/initgpt

Initialize Disk with GPT

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| disk | string | yes | Block device name |
| uuid | string | no | UUID for the GPT table |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Initialize Disk with GPT",
  "method": "POST",
  "name": "initgpt",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "disk": {
        "description": "Block device name",
        "pattern": "^/dev/[a-zA-Z0-9\\/]+$",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "uuid": {
        "description": "UUID for the GPT table",
        "maxLength": 36,
        "optional": 1,
        "pattern": "[a-fA-F0-9\\-]+",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
