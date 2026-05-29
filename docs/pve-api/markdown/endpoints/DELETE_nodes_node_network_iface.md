# DELETE /nodes/{node}/network/{iface}

Delete network device configuration

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| iface | string | yes | Network interface name. |
| node | string | yes | The cluster node name. |

## Request parameters

None.

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
  "description": "Delete network device configuration",
  "method": "DELETE",
  "name": "delete_network",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "iface": {
        "description": "Network interface name.",
        "format": "pve-iface",
        "maxLength": 20,
        "minLength": 2,
        "type": "string",
        "typetext": "<string>"
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
