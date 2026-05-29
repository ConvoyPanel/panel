# PUT /nodes/{node}/ceph/pool/{name}

Change POOL settings

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the pool. It must be unique. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| application | string | no | The application of the pool. |
| crush_rule | string | no | The rule to use for mapping object placement in the cluster. |
| min_size | integer | no | Minimum number of replicas per object |
| pg_autoscale_mode | string | no | The automatic PG scaling mode of the pool. |
| pg_num | integer | no | Number of placement groups. |
| pg_num_min | integer | no | Minimal number of placement groups. |
| size | integer | no | Number of replicas per object |
| target_size | string | no | The estimated target size of the pool for the PG autoscaler. |
| target_size_ratio | number | no | The estimated target ratio of the pool for the PG autoscaler. |

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
  "description": "Change POOL settings",
  "method": "PUT",
  "name": "setpool",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "application": {
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
      "crush_rule": {
        "description": "The rule to use for mapping object placement in the cluster.",
        "optional": 1,
        "title": "Crush Rule Name",
        "type": "string",
        "typetext": "<string>"
      },
      "min_size": {
        "description": "Minimum number of replicas per object",
        "maximum": 7,
        "minimum": 1,
        "optional": 1,
        "title": "Min Size",
        "type": "integer",
        "typetext": "<integer> (1 - 7)"
      },
      "name": {
        "description": "The name of the pool. It must be unique.",
        "pattern": "(?^:^[^:/\\s]+$)",
        "title": "Name",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pg_autoscale_mode": {
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
        "description": "Number of placement groups.",
        "maximum": 32768,
        "minimum": 1,
        "optional": 1,
        "title": "PG Num",
        "type": "integer",
        "typetext": "<integer> (1 - 32768)"
      },
      "pg_num_min": {
        "description": "Minimal number of placement groups.",
        "maximum": 32768,
        "optional": 1,
        "title": "min. PG Num",
        "type": "integer",
        "typetext": "<integer> (-N - 32768)"
      },
      "size": {
        "description": "Number of replicas per object",
        "maximum": 7,
        "minimum": 1,
        "optional": 1,
        "title": "Size",
        "type": "integer",
        "typetext": "<integer> (1 - 7)"
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
        "type": "number",
        "typetext": "<number>"
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
