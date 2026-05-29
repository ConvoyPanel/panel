# POST /nodes/{node}/ceph/pool

Create Ceph pool

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The name of the pool. It must be unique. |
| add_storages | boolean | no | Configure VM and CT storage using the new pool. Defaults to false for replicated pools and to true for erasure-coded pools (since EC pools are typically only useful when wired up to storage). |
| application | string | no | The application of the pool. |
| crush_rule | string | no | The rule to use for mapping object placement in the cluster. |
| erasure-coding | string | no | Create an erasure coded pool for RBD with an accompaning replicated pool for metadata storage. With EC, the common ceph options 'size', 'min_size' and 'crush_rule' parameters will be applied to the metadata pool. |
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
  "description": "Create Ceph pool",
  "method": "POST",
  "name": "createpool",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "add_storages": {
        "default": 0,
        "description": "Configure VM and CT storage using the new pool. Defaults to false for replicated pools and to true for erasure-coded pools (since EC pools are typically only useful when wired up to storage).",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
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
      "crush_rule": {
        "description": "The rule to use for mapping object placement in the cluster.",
        "optional": 1,
        "title": "Crush Rule Name",
        "type": "string",
        "typetext": "<string>"
      },
      "erasure-coding": {
        "description": "Create an erasure coded pool for RBD with an accompaning replicated pool for metadata storage. With EC, the common ceph options 'size', 'min_size' and 'crush_rule' parameters will be applied to the metadata pool.",
        "format": {
          "device-class": {
            "description": "CRUSH device class. Will create an erasure coded pool plus a replicated pool for metadata.",
            "format_description": "class",
            "optional": 1,
            "type": "string"
          },
          "failure-domain": {
            "default": "host",
            "description": "CRUSH failure domain. Default is 'host'. Will create an erasure coded pool plus a replicated pool for metadata.",
            "format_description": "domain",
            "optional": 1,
            "type": "string"
          },
          "k": {
            "description": "Number of data chunks. Will create an erasure coded pool plus a replicated pool for metadata.",
            "minimum": 2,
            "type": "integer"
          },
          "m": {
            "description": "Number of coding chunks. Will create an erasure coded pool plus a replicated pool for metadata.",
            "minimum": 1,
            "type": "integer"
          },
          "profile": {
            "description": "Override the erasure code (EC) profile to use. Will create an erasure coded pool plus a replicated pool for metadata.",
            "format_description": "profile",
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "k=<integer> ,m=<integer> [,device-class=<class>] [,failure-domain=<domain>] [,profile=<profile>]"
      },
      "min_size": {
        "default": 2,
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
        "default": 3,
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
