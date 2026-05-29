# GET /nodes/{node}/ceph/pool/{name}/status

Show the current pool status.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the pool. It must be unique. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| verbose | boolean | no | If enabled, will display additional data(eg. statistics). |

## Returns

```json
{
  "properties": {
    "application": {
      "default": "rbd",
      "description": "The application of the pool.",
      "enum": [
        "rbd",
        "cephfs",
        "rgw"
      ],
      "optional": 1,
      "title": "Application",
      "type": "string"
    },
    "application_list": {
      "description": "Names of applications currently associated with the pool.",
      "items": {
        "description": "Application name (e.g. 'rbd', 'cephfs', 'rgw').",
        "type": "string"
      },
      "optional": 1,
      "title": "Application",
      "type": "array"
    },
    "autoscale_status": {
      "description": "Raw pg_autoscaler status object for this pool; shape varies between Ceph releases.",
      "optional": 1,
      "title": "Autoscale Status",
      "type": "object"
    },
    "crush_rule": {
      "description": "The rule to use for mapping object placement in the cluster.",
      "optional": 1,
      "title": "Crush Rule Name",
      "type": "string"
    },
    "fast_read": {
      "description": "Set if the pool uses fast-read for erasure-coded reads.",
      "title": "Fast Read",
      "type": "boolean"
    },
    "hashpspool": {
      "description": "Set if the pool hashes pool id into its CRUSH placement-seed.",
      "title": "hashpspool",
      "type": "boolean"
    },
    "id": {
      "description": "Numeric pool id assigned by Ceph.",
      "title": "ID",
      "type": "integer"
    },
    "min_size": {
      "default": 2,
      "description": "Minimum number of replicas per object",
      "maximum": 7,
      "minimum": 1,
      "optional": 1,
      "title": "Min Size",
      "type": "integer"
    },
    "name": {
      "description": "The name of the pool. It must be unique.",
      "pattern": "(?^:^[^:/\\s]+$)",
      "title": "Name",
      "type": "string"
    },
    "nodeep-scrub": {
      "description": "Set if deep-scrubbing is disabled for this pool.",
      "title": "nodeep-scrub",
      "type": "boolean"
    },
    "nodelete": {
      "description": "Set if pool delete is blocked.",
      "title": "nodelete",
      "type": "boolean"
    },
    "nopgchange": {
      "description": "Set if changing the placement-group count is blocked.",
      "title": "nopgchange",
      "type": "boolean"
    },
    "noscrub": {
      "description": "Set if scrubbing is disabled for this pool.",
      "title": "noscrub",
      "type": "boolean"
    },
    "nosizechange": {
      "description": "Set if changing the replication size is blocked.",
      "title": "nosizechange",
      "type": "boolean"
    },
    "pg_autoscale_mode": {
      "default": "warn",
      "description": "The automatic PG scaling mode of the pool.",
      "enum": [
        "on",
        "off",
        "warn"
      ],
      "optional": 1,
      "title": "PG Autoscale Mode",
      "type": "string"
    },
    "pg_num": {
      "default": 128,
      "description": "Number of placement groups.",
      "maximum": 32768,
      "minimum": 1,
      "optional": 1,
      "title": "PG Num",
      "type": "integer"
    },
    "pg_num_min": {
      "description": "Minimal number of placement groups.",
      "maximum": 32768,
      "optional": 1,
      "title": "min. PG Num",
      "type": "integer"
    },
    "pgp_num": {
      "description": "Placement-group-for-placement count.",
      "title": "PGP num",
      "type": "integer"
    },
    "size": {
      "default": 3,
      "description": "Number of replicas per object",
      "maximum": 7,
      "minimum": 1,
      "optional": 1,
      "title": "Size",
      "type": "integer"
    },
    "statistics": {
      "description": "Optional pool usage and IO statistics (only present when verbose=1 is requested).",
      "optional": 1,
      "title": "Statistics",
      "type": "object"
    },
    "target_size": {
      "description": "The estimated target size of the pool for the PG autoscaler.",
      "optional": 1,
      "pattern": "^(\\d+(\\.\\d+)?)([KMGT])?$",
      "title": "PG Autoscale Target Size",
      "type": "string"
    },
    "target_size_ratio": {
      "description": "The estimated target ratio of the pool for the PG autoscaler.",
      "optional": 1,
      "title": "PG Autoscale Target Ratio",
      "type": "number"
    },
    "use_gmt_hitset": {
      "description": "Set if hitsets use GMT timestamps (for cache-tier pools).",
      "title": "use_gmt_hitset",
      "type": "boolean"
    },
    "write_fadvise_dontneed": {
      "description": "Set if the pool sets the FADV_DONTNEED hint on writes.",
      "title": "write_fadvise_dontneed",
      "type": "boolean"
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
  "description": "Show the current pool status.",
  "method": "GET",
  "name": "getpool",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "The name of the pool. It must be unique.",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "verbose": {
        "default": 0,
        "description": "If enabled, will display additional data(eg. statistics).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "properties": {
      "application": {
        "default": "rbd",
        "description": "The application of the pool.",
        "enum": [
          "rbd",
          "cephfs",
          "rgw"
        ],
        "optional": 1,
        "title": "Application",
        "type": "string"
      },
      "application_list": {
        "description": "Names of applications currently associated with the pool.",
        "items": {
          "description": "Application name (e.g. 'rbd', 'cephfs', 'rgw').",
          "type": "string"
        },
        "optional": 1,
        "title": "Application",
        "type": "array"
      },
      "autoscale_status": {
        "description": "Raw pg_autoscaler status object for this pool; shape varies between Ceph releases.",
        "optional": 1,
        "title": "Autoscale Status",
        "type": "object"
      },
      "crush_rule": {
        "description": "The rule to use for mapping object placement in the cluster.",
        "optional": 1,
        "title": "Crush Rule Name",
        "type": "string"
      },
      "fast_read": {
        "description": "Set if the pool uses fast-read for erasure-coded reads.",
        "title": "Fast Read",
        "type": "boolean"
      },
      "hashpspool": {
        "description": "Set if the pool hashes pool id into its CRUSH placement-seed.",
        "title": "hashpspool",
        "type": "boolean"
      },
      "id": {
        "description": "Numeric pool id assigned by Ceph.",
        "title": "ID",
        "type": "integer"
      },
      "min_size": {
        "default": 2,
        "description": "Minimum number of replicas per object",
        "maximum": 7,
        "minimum": 1,
        "optional": 1,
        "title": "Min Size",
        "type": "integer"
      },
      "name": {
        "description": "The name of the pool. It must be unique.",
        "pattern": "(?^:^[^:/\\s]+$)",
        "title": "Name",
        "type": "string"
      },
      "nodeep-scrub": {
        "description": "Set if deep-scrubbing is disabled for this pool.",
        "title": "nodeep-scrub",
        "type": "boolean"
      },
      "nodelete": {
        "description": "Set if pool delete is blocked.",
        "title": "nodelete",
        "type": "boolean"
      },
      "nopgchange": {
        "description": "Set if changing the placement-group count is blocked.",
        "title": "nopgchange",
        "type": "boolean"
      },
      "noscrub": {
        "description": "Set if scrubbing is disabled for this pool.",
        "title": "noscrub",
        "type": "boolean"
      },
      "nosizechange": {
        "description": "Set if changing the replication size is blocked.",
        "title": "nosizechange",
        "type": "boolean"
      },
      "pg_autoscale_mode": {
        "default": "warn",
        "description": "The automatic PG scaling mode of the pool.",
        "enum": [
          "on",
          "off",
          "warn"
        ],
        "optional": 1,
        "title": "PG Autoscale Mode",
        "type": "string"
      },
      "pg_num": {
        "default": 128,
        "description": "Number of placement groups.",
        "maximum": 32768,
        "minimum": 1,
        "optional": 1,
        "title": "PG Num",
        "type": "integer"
      },
      "pg_num_min": {
        "description": "Minimal number of placement groups.",
        "maximum": 32768,
        "optional": 1,
        "title": "min. PG Num",
        "type": "integer"
      },
      "pgp_num": {
        "description": "Placement-group-for-placement count.",
        "title": "PGP num",
        "type": "integer"
      },
      "size": {
        "default": 3,
        "description": "Number of replicas per object",
        "maximum": 7,
        "minimum": 1,
        "optional": 1,
        "title": "Size",
        "type": "integer"
      },
      "statistics": {
        "description": "Optional pool usage and IO statistics (only present when verbose=1 is requested).",
        "optional": 1,
        "title": "Statistics",
        "type": "object"
      },
      "target_size": {
        "description": "The estimated target size of the pool for the PG autoscaler.",
        "optional": 1,
        "pattern": "^(\\d+(\\.\\d+)?)([KMGT])?$",
        "title": "PG Autoscale Target Size",
        "type": "string"
      },
      "target_size_ratio": {
        "description": "The estimated target ratio of the pool for the PG autoscaler.",
        "optional": 1,
        "title": "PG Autoscale Target Ratio",
        "type": "number"
      },
      "use_gmt_hitset": {
        "description": "Set if hitsets use GMT timestamps (for cache-tier pools).",
        "title": "use_gmt_hitset",
        "type": "boolean"
      },
      "write_fadvise_dontneed": {
        "description": "Set if the pool sets the FADV_DONTNEED hint on writes.",
        "title": "write_fadvise_dontneed",
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
