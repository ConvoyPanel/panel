# PUT /nodes/{node}/subscription

Set subscription key.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| key | string | yes | Proxmox VE subscription key |

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
    "/nodes/{node}",
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
  "description": "Set subscription key.",
  "method": "PUT",
  "name": "set",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "key": {
        "description": "Proxmox VE subscription key",
        "maxLength": 32,
        "pattern": "\\s*pve([1248])([cbsp])-[0-9a-f]{10}\\s*",
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
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
