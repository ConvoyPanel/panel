# POST /nodes/{node}/wakeonlan

Try to wake a node via 'wake on LAN' network packet.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | target node for wake on LAN packet |

## Request parameters

None.

## Returns

```json
{
  "description": "MAC address used to assemble the WoL magic packet.",
  "format": "mac-addr",
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
      "Sys.PowerMgmt"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Try to wake a node via 'wake on LAN' network packet.",
  "method": "POST",
  "name": "wakeonlan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "target node for wake on LAN packet",
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
        "Sys.PowerMgmt"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "description": "MAC address used to assemble the WoL magic packet.",
    "format": "mac-addr",
    "type": "string"
  }
}
```
