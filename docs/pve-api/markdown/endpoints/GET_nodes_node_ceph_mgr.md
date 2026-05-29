# GET /nodes/{node}/ceph/mgr

MGR directory index.

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
        "description": "Address as advertised by the manager; Ceph-formatted (typically 'IP:PORT/NONCE').",
        "optional": 1,
        "type": "string"
      },
      "ceph_version": {
        "description": "Full Ceph version string of the manager daemon.",
        "optional": 1,
        "type": "string"
      },
      "ceph_version_short": {
        "description": "Short Ceph version string of the manager daemon (e.g. '19.2.0').",
        "optional": 1,
        "type": "string"
      },
      "direxists": {
        "description": "Set when the manager's data directory exists on this node.",
        "optional": 1,
        "type": "boolean"
      },
      "host": {
        "description": "Host the manager runs on.",
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "The name (ID) for the MGR.",
        "type": "string"
      },
      "service": {
        "description": "Set if a ceph-mgr@<id> systemd unit is enabled on the hosting node; absent otherwise.",
        "optional": 1,
        "type": "boolean"
      },
      "state": {
        "description": "Manager state: 'active' or 'standby' for daemons visible to the mgr cluster, 'stopped' or 'unknown' for configured daemons not currently visible.",
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
  "description": "MGR directory index.",
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
          "description": "Address as advertised by the manager; Ceph-formatted (typically 'IP:PORT/NONCE').",
          "optional": 1,
          "type": "string"
        },
        "ceph_version": {
          "description": "Full Ceph version string of the manager daemon.",
          "optional": 1,
          "type": "string"
        },
        "ceph_version_short": {
          "description": "Short Ceph version string of the manager daemon (e.g. '19.2.0').",
          "optional": 1,
          "type": "string"
        },
        "direxists": {
          "description": "Set when the manager's data directory exists on this node.",
          "optional": 1,
          "type": "boolean"
        },
        "host": {
          "description": "Host the manager runs on.",
          "optional": 1,
          "type": "string"
        },
        "name": {
          "description": "The name (ID) for the MGR.",
          "type": "string"
        },
        "service": {
          "description": "Set if a ceph-mgr@<id> systemd unit is enabled on the hosting node; absent otherwise.",
          "optional": 1,
          "type": "boolean"
        },
        "state": {
          "description": "Manager state: 'active' or 'standby' for daemons visible to the mgr cluster, 'stopped' or 'unknown' for configured daemons not currently visible.",
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
