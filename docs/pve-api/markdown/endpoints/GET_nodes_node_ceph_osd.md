# GET /nodes/{node}/ceph/osd

Get Ceph osd list/tree.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 1,
  "properties": {
    "flags": {
      "description": "Comma-joined list of currently-set OSD flags; absent when no flags are set on the cluster.",
      "optional": 1,
      "type": "string"
    },
    "root": {
      "additionalProperties": 1,
      "description": "Top-level CRUSH bucket; recursive structure with 'children' lists holding nested buckets and OSD leaves. Per-node properties (status, weight, in, usage, latencies, etc.) vary by node type and are not statically typed here.",
      "type": "object"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit",
      "Datastore.Audit"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get Ceph osd list/tree.",
  "method": "GET",
  "name": "index",
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
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit",
        "Datastore.Audit"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "additionalProperties": 1,
    "properties": {
      "flags": {
        "description": "Comma-joined list of currently-set OSD flags; absent when no flags are set on the cluster.",
        "optional": 1,
        "type": "string"
      },
      "root": {
        "additionalProperties": 1,
        "description": "Top-level CRUSH bucket; recursive structure with 'children' lists holding nested buckets and OSD leaves. Per-node properties (status, weight, in, usage, latencies, etc.) vary by node type and are not statically typed here.",
        "type": "object"
      }
    },
    "type": "object"
  }
}
```
