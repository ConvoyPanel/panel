# PUT /nodes/{node}/dns

Write DNS settings.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| search | string | yes | Search domain for host-name lookup. |
| dns1 | string | no | First name server IP address. |
| dns2 | string | no | Second name server IP address. |
| dns3 | string | no | Third name server IP address. |

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
  "description": "Write DNS settings.",
  "method": "PUT",
  "name": "update_dns",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "dns1": {
        "description": "First name server IP address.",
        "format": "ip",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dns2": {
        "description": "Second name server IP address.",
        "format": "ip",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "dns3": {
        "description": "Third name server IP address.",
        "format": "ip",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "search": {
        "description": "Search domain for host-name lookup.",
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
