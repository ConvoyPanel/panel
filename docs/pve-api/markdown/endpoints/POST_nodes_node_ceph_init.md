# POST /nodes/{node}/ceph/init

Create the initial Ceph default configuration and set up symlinks. Idempotent on re-call: if a [global] section already exists in ceph.conf, the existing fsid / auth / pool defaults are preserved and most parameters are silently ignored.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cluster-network | string | no | Declare a separate cluster network, OSDs will route heartbeat, object replication and recovery traffic over it |
| disable_cephx | boolean | no | Disable cephx authentication.  WARNING: cephx is a security feature protecting against man-in-the-middle attacks. Only consider disabling cephx if your network is private! |
| min_size | integer | no | Minimum number of available replicas per object to allow I/O |
| network | string | no | Use specific network for all ceph related traffic |
| pg_bits | integer | no | Placement group bits, used to specify the default number of placement groups.  Depreacted. This setting was deprecated in recent Ceph versions. |
| size | integer | no | Targeted number of replicas per object |

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
  "description": "Create the initial Ceph default configuration and set up symlinks. Idempotent on re-call: if a [global] section already exists in ceph.conf, the existing fsid / auth / pool defaults are preserved and most parameters are silently ignored.",
  "method": "POST",
  "name": "init",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cluster-network": {
        "description": "Declare a separate cluster network, OSDs will route heartbeat, object replication and recovery traffic over it",
        "format": "CIDR",
        "maxLength": 128,
        "optional": 1,
        "requires": "network",
        "type": "string",
        "typetext": "<string>"
      },
      "disable_cephx": {
        "default": 0,
        "description": "Disable cephx authentication.\n\nWARNING: cephx is a security feature protecting against man-in-the-middle attacks. Only consider disabling cephx if your network is private!",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "min_size": {
        "default": 2,
        "description": "Minimum number of available replicas per object to allow I/O",
        "maximum": 7,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 7)"
      },
      "network": {
        "description": "Use specific network for all ceph related traffic",
        "format": "CIDR",
        "maxLength": 128,
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
      "pg_bits": {
        "default": 6,
        "description": "Placement group bits, used to specify the default number of placement groups.\n\nDepreacted. This setting was deprecated in recent Ceph versions.",
        "maximum": 14,
        "minimum": 6,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (6 - 14)"
      },
      "size": {
        "default": 3,
        "description": "Targeted number of replicas per object",
        "maximum": 7,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 7)"
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
    "type": "null"
  }
}
```
