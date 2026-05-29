# POST /nodes/{node}/ceph/mon/{monid}

Create a Ceph Monitor. Also auto-creates a Manager for the first monitor.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| monid | string | no | The ID for the monitor, when omitted the same as the nodename. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| mon-address | string | no | Overwrites autodetected monitor IP address(es). Must be in the public network(s) of Ceph. |

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
  "description": "Create a Ceph Monitor. Also auto-creates a Manager for the first monitor.",
  "method": "POST",
  "name": "createmon",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "mon-address": {
        "description": "Overwrites autodetected monitor IP address(es). Must be in the public network(s) of Ceph.",
        "format": "ip-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "monid": {
        "default": "nodename",
        "description": "The ID for the monitor, when omitted the same as the nodename.",
        "maxLength": 200,
        "optional": 1,
        "pattern": "[a-zA-Z0-9]([a-zA-Z0-9\\-]*[a-zA-Z0-9])?",
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
