# GET /nodes/{node}/version

API version details

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "release": {
      "description": "The current installed Proxmox VE Release",
      "type": "string"
    },
    "repoid": {
      "description": "The short git commit hash ID from which this version was build",
      "type": "string"
    },
    "version": {
      "description": "The current installed pve-manager package version",
      "type": "string"
    }
  },
  "type": "object"
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
  "description": "API version details",
  "method": "GET",
  "name": "version",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
    "properties": {
      "release": {
        "description": "The current installed Proxmox VE Release",
        "type": "string"
      },
      "repoid": {
        "description": "The short git commit hash ID from which this version was build",
        "type": "string"
      },
      "version": {
        "description": "The current installed pve-manager package version",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
