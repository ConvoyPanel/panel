# GET /nodes/{node}/ceph/mon

Get Ceph monitor list.

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
        "description": "Address as advertised by the monitor; Ceph-formatted (typically 'IP:PORT/NONCE', possibly as a messenger-v2 vector depending on Ceph version and ceph.conf shape).",
        "optional": 1,
        "type": "string"
      },
      "ceph_version": {
        "description": "Full Ceph version string of the monitor daemon.",
        "optional": 1,
        "type": "string"
      },
      "ceph_version_short": {
        "description": "Short Ceph version string of the monitor daemon (e.g. '19.2.0').",
        "optional": 1,
        "type": "string"
      },
      "direxists": {
        "description": "Set when the monitor's data directory exists on this node.",
        "optional": 1,
        "type": "boolean"
      },
      "host": {
        "description": "Host the monitor runs on.",
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "Monitor id (typically the hostname).",
        "type": "string"
      },
      "quorum": {
        "description": "Set when the monitor is part of the current quorum.",
        "optional": 1,
        "type": "boolean"
      },
      "rank": {
        "description": "Rank of the monitor within the mon map.",
        "optional": 1,
        "type": "integer"
      },
      "service": {
        "description": "Set if a ceph-mon@<id> systemd unit is enabled on the hosting node; absent otherwise.",
        "optional": 1,
        "type": "boolean"
      },
      "state": {
        "description": "Run state of the monitor: 'running' (in quorum), 'stopped' (systemd unit configured but daemon not visible to the cluster), or 'unknown' (no rados access).",
        "optional": 1,
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
  "description": "Get Ceph monitor list.",
  "method": "GET",
  "name": "listmon",
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
          "description": "Address as advertised by the monitor; Ceph-formatted (typically 'IP:PORT/NONCE', possibly as a messenger-v2 vector depending on Ceph version and ceph.conf shape).",
          "optional": 1,
          "type": "string"
        },
        "ceph_version": {
          "description": "Full Ceph version string of the monitor daemon.",
          "optional": 1,
          "type": "string"
        },
        "ceph_version_short": {
          "description": "Short Ceph version string of the monitor daemon (e.g. '19.2.0').",
          "optional": 1,
          "type": "string"
        },
        "direxists": {
          "description": "Set when the monitor's data directory exists on this node.",
          "optional": 1,
          "type": "boolean"
        },
        "host": {
          "description": "Host the monitor runs on.",
          "optional": 1,
          "type": "string"
        },
        "name": {
          "description": "Monitor id (typically the hostname).",
          "type": "string"
        },
        "quorum": {
          "description": "Set when the monitor is part of the current quorum.",
          "optional": 1,
          "type": "boolean"
        },
        "rank": {
          "description": "Rank of the monitor within the mon map.",
          "optional": 1,
          "type": "integer"
        },
        "service": {
          "description": "Set if a ceph-mon@<id> systemd unit is enabled on the hosting node; absent otherwise.",
          "optional": 1,
          "type": "boolean"
        },
        "state": {
          "description": "Run state of the monitor: 'running' (in quorum), 'stopped' (systemd unit configured but daemon not visible to the cluster), or 'unknown' (no rados access).",
          "optional": 1,
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
