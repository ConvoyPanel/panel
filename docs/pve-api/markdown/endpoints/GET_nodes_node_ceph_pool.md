# GET /nodes/{node}/ceph/pool

List all pools and their settings (which are settable by the POST/PUT endpoints).

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
      "application_metadata": {
        "description": "Application tags attached to the pool (mapping of application name to its metadata object).",
        "optional": 1,
        "title": "Associated Applications",
        "type": "object"
      },
      "autoscale_status": {
        "description": "Raw pg_autoscaler status object for this pool; shape varies between Ceph releases.",
        "optional": 1,
        "title": "Autoscale Status",
        "type": "object"
      },
      "bytes_used": {
        "description": "Bytes currently used in the pool; absent if no usage statistics are reported.",
        "optional": 1,
        "renderer": "bytes",
        "title": "Used",
        "type": "integer"
      },
      "crush_rule": {
        "description": "Numeric id of the CRUSH rule used by this pool.",
        "title": "Crush Rule",
        "type": "integer"
      },
      "crush_rule_name": {
        "description": "Human-readable name of the CRUSH rule used by this pool; absent if the rule id is not in the current CRUSH map.",
        "optional": 1,
        "title": "Crush Rule Name",
        "type": "string"
      },
      "min_size": {
        "description": "Minimum number of replicas required to accept writes.",
        "title": "Min Size",
        "type": "integer"
      },
      "percent_used": {
        "description": "Percentage of pool capacity currently used; absent if no usage statistics are reported.",
        "optional": 1,
        "title": "%-Used",
        "type": "number"
      },
      "pg_autoscale_mode": {
        "description": "Placement-group autoscaler mode ('on', 'warn' or 'off').",
        "optional": 1,
        "title": "PG Autoscale Mode",
        "type": "string"
      },
      "pg_num": {
        "description": "Current placement-group count.",
        "title": "PG Num",
        "type": "integer"
      },
      "pg_num_final": {
        "description": "Optimal placement-group count computed by pg_autoscaler.",
        "optional": 1,
        "title": "Optimal PG Num",
        "type": "integer"
      },
      "pg_num_min": {
        "description": "Minimum placement-group count the pg_autoscaler may choose.",
        "optional": 1,
        "title": "min. PG Num",
        "type": "integer"
      },
      "pool": {
        "description": "Numeric pool id assigned by Ceph.",
        "title": "ID",
        "type": "integer"
      },
      "pool_name": {
        "description": "Operator-visible name of the pool.",
        "title": "Name",
        "type": "string"
      },
      "size": {
        "description": "Replication factor (target number of object replicas).",
        "title": "Size",
        "type": "integer"
      },
      "target_size": {
        "description": "Operator-supplied target size in bytes; hints the pg_autoscaler.",
        "optional": 1,
        "title": "PG Autoscale Target Size",
        "type": "integer"
      },
      "target_size_ratio": {
        "description": "Operator-supplied target ratio of total pool capacity; hints the pg_autoscaler.",
        "optional": 1,
        "title": "PG Autoscale Target Ratio",
        "type": "number"
      },
      "type": {
        "description": "Pool type: 'replicated' for n-way replication, 'erasure' for an erasure-coded pool, 'unknown' for types PVE does not yet map.",
        "enum": [
          "replicated",
          "erasure",
          "unknown"
        ],
        "title": "Type",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{pool_name}",
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
  "description": "List all pools and their settings (which are settable by the POST/PUT endpoints).",
  "method": "GET",
  "name": "lspools",
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
        "application_metadata": {
          "description": "Application tags attached to the pool (mapping of application name to its metadata object).",
          "optional": 1,
          "title": "Associated Applications",
          "type": "object"
        },
        "autoscale_status": {
          "description": "Raw pg_autoscaler status object for this pool; shape varies between Ceph releases.",
          "optional": 1,
          "title": "Autoscale Status",
          "type": "object"
        },
        "bytes_used": {
          "description": "Bytes currently used in the pool; absent if no usage statistics are reported.",
          "optional": 1,
          "renderer": "bytes",
          "title": "Used",
          "type": "integer"
        },
        "crush_rule": {
          "description": "Numeric id of the CRUSH rule used by this pool.",
          "title": "Crush Rule",
          "type": "integer"
        },
        "crush_rule_name": {
          "description": "Human-readable name of the CRUSH rule used by this pool; absent if the rule id is not in the current CRUSH map.",
          "optional": 1,
          "title": "Crush Rule Name",
          "type": "string"
        },
        "min_size": {
          "description": "Minimum number of replicas required to accept writes.",
          "title": "Min Size",
          "type": "integer"
        },
        "percent_used": {
          "description": "Percentage of pool capacity currently used; absent if no usage statistics are reported.",
          "optional": 1,
          "title": "%-Used",
          "type": "number"
        },
        "pg_autoscale_mode": {
          "description": "Placement-group autoscaler mode ('on', 'warn' or 'off').",
          "optional": 1,
          "title": "PG Autoscale Mode",
          "type": "string"
        },
        "pg_num": {
          "description": "Current placement-group count.",
          "title": "PG Num",
          "type": "integer"
        },
        "pg_num_final": {
          "description": "Optimal placement-group count computed by pg_autoscaler.",
          "optional": 1,
          "title": "Optimal PG Num",
          "type": "integer"
        },
        "pg_num_min": {
          "description": "Minimum placement-group count the pg_autoscaler may choose.",
          "optional": 1,
          "title": "min. PG Num",
          "type": "integer"
        },
        "pool": {
          "description": "Numeric pool id assigned by Ceph.",
          "title": "ID",
          "type": "integer"
        },
        "pool_name": {
          "description": "Operator-visible name of the pool.",
          "title": "Name",
          "type": "string"
        },
        "size": {
          "description": "Replication factor (target number of object replicas).",
          "title": "Size",
          "type": "integer"
        },
        "target_size": {
          "description": "Operator-supplied target size in bytes; hints the pg_autoscaler.",
          "optional": 1,
          "title": "PG Autoscale Target Size",
          "type": "integer"
        },
        "target_size_ratio": {
          "description": "Operator-supplied target ratio of total pool capacity; hints the pg_autoscaler.",
          "optional": 1,
          "title": "PG Autoscale Target Ratio",
          "type": "number"
        },
        "type": {
          "description": "Pool type: 'replicated' for n-way replication, 'erasure' for an erasure-coded pool, 'unknown' for types PVE does not yet map.",
          "enum": [
            "replicated",
            "erasure",
            "unknown"
          ],
          "title": "Type",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{pool_name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
