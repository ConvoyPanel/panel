# PUT /nodes/{node}/network

Reload network configuration

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| regenerate-frr | boolean | no | Whether FRR config generation should get skipped or not. |

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
  "description": "Reload network configuration",
  "method": "PUT",
  "name": "reload_network_config",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "regenerate-frr": {
        "default": 0,
        "description": "Whether FRR config generation should get skipped or not.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "type": "string"
  }
}
```
