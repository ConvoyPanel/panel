# GET /nodes/{node}/ceph/mds

MDS directory index.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "addr": {
        "description": "Address as advertised by the MDS; Ceph-formatted (typically 'IP:PORT/NONCE').",
        "optional": 1,
        "type": "string"
      },
      "ceph_version": {
        "description": "Full Ceph version string of the MDS daemon.",
        "optional": 1,
        "type": "string"
      },
      "ceph_version_short": {
        "description": "Short Ceph version string of the MDS daemon (e.g. '19.2.0').",
        "optional": 1,
        "type": "string"
      },
      "direxists": {
        "description": "Set when the MDS's data directory exists on this node.",
        "optional": 1,
        "type": "boolean"
      },
      "fs_name": {
        "description": "Name of the CephFS this MDS is bound to; absent or null for standby MDSes not currently serving a rank.",
        "optional": 1,
        "type": "string"
      },
      "host": {
        "description": "Host the MDS runs on.",
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "The name (ID) for the MDS.",
        "type": "string"
      },
      "rank": {
        "description": "MDS rank within the file system; -1 for standby MDSes not currently bound to a rank.",
        "optional": 1,
        "type": "integer"
      },
      "service": {
        "description": "Set if a ceph-mds@<id> systemd unit is enabled on the hosting node; absent otherwise.",
        "optional": 1,
        "type": "boolean"
      },
      "standby_replay": {
        "description": "If true, the standby MDS is polling the active MDS for faster recovery (hot standby).",
        "optional": 1,
        "type": "boolean"
      },
      "state": {
        "description": "MDS state: Ceph-reported run state (e.g. 'up:active', 'up:standby', 'up:standby-replay') for daemons known to the cluster; 'stopped' or 'unknown' for configured daemons not visible to the cluster.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{name}",
      "rel": "child"
    }
  ],
  "type": "array"
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
  "description": "MDS directory index.",
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
    "items": {
      "properties": {
        "addr": {
          "description": "Address as advertised by the MDS; Ceph-formatted (typically 'IP:PORT/NONCE').",
          "optional": 1,
          "type": "string"
        },
        "ceph_version": {
          "description": "Full Ceph version string of the MDS daemon.",
          "optional": 1,
          "type": "string"
        },
        "ceph_version_short": {
          "description": "Short Ceph version string of the MDS daemon (e.g. '19.2.0').",
          "optional": 1,
          "type": "string"
        },
        "direxists": {
          "description": "Set when the MDS's data directory exists on this node.",
          "optional": 1,
          "type": "boolean"
        },
        "fs_name": {
          "description": "Name of the CephFS this MDS is bound to; absent or null for standby MDSes not currently serving a rank.",
          "optional": 1,
          "type": "string"
        },
        "host": {
          "description": "Host the MDS runs on.",
          "optional": 1,
          "type": "string"
        },
        "name": {
          "description": "The name (ID) for the MDS.",
          "type": "string"
        },
        "rank": {
          "description": "MDS rank within the file system; -1 for standby MDSes not currently bound to a rank.",
          "optional": 1,
          "type": "integer"
        },
        "service": {
          "description": "Set if a ceph-mds@<id> systemd unit is enabled on the hosting node; absent otherwise.",
          "optional": 1,
          "type": "boolean"
        },
        "standby_replay": {
          "description": "If true, the standby MDS is polling the active MDS for faster recovery (hot standby).",
          "optional": 1,
          "type": "boolean"
        },
        "state": {
          "description": "MDS state: Ceph-reported run state (e.g. 'up:active', 'up:standby', 'up:standby-replay') for daemons known to the cluster; 'stopped' or 'unknown' for configured daemons not visible to the cluster.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
