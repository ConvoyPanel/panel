# POST /nodes/{node}/ceph/mds/{name}

Create Ceph Metadata Server (MDS)

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| name | string | no | The ID for the mds, when omitted the same as the nodename |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| hotstandby | boolean | no | Determines whether a ceph-mds daemon should poll and replay the log of an active MDS. Faster switch on MDS failure, but needs more idle resources. |

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
  "description": "Create Ceph Metadata Server (MDS)",
  "method": "POST",
  "name": "createmds",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "hotstandby": {
        "default": 0,
        "description": "Determines whether a ceph-mds daemon should poll and replay the log of an active MDS. Faster switch on MDS failure, but needs more idle resources.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "name": {
        "default": "nodename",
        "description": "The ID for the mds, when omitted the same as the nodename",
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
