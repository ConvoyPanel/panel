# GET /nodes/{node}/ceph/fs

Directory index.

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
    "additionalProperties": 1,
    "properties": {
      "data_pool": {
        "description": "Name of the filesystem's first data pool. A CephFS can have more than one data pool; consumers interested in the full set should read 'data_pools' instead. Kept for backwards compatibility.",
        "type": "string"
      },
      "data_pool_ids": {
        "description": "Numeric ids of the data pools.",
        "items": {
          "description": "Data pool id.",
          "type": "integer"
        },
        "optional": 1,
        "type": "array"
      },
      "data_pools": {
        "description": "Names of all data pools assigned to the filesystem; a CephFS can have multiple data pools (e.g. replicated metadata plus EC data, or multiple device-class-specific data pools).",
        "items": {
          "description": "Data pool name.",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "metadata_pool": {
        "description": "Name of the metadata pool.",
        "type": "string"
      },
      "metadata_pool_id": {
        "description": "Numeric id of the metadata pool.",
        "optional": 1,
        "type": "integer"
      },
      "name": {
        "description": "The ceph filesystem name.",
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
  "description": "Directory index.",
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
      "additionalProperties": 1,
      "properties": {
        "data_pool": {
          "description": "Name of the filesystem's first data pool. A CephFS can have more than one data pool; consumers interested in the full set should read 'data_pools' instead. Kept for backwards compatibility.",
          "type": "string"
        },
        "data_pool_ids": {
          "description": "Numeric ids of the data pools.",
          "items": {
            "description": "Data pool id.",
            "type": "integer"
          },
          "optional": 1,
          "type": "array"
        },
        "data_pools": {
          "description": "Names of all data pools assigned to the filesystem; a CephFS can have multiple data pools (e.g. replicated metadata plus EC data, or multiple device-class-specific data pools).",
          "items": {
            "description": "Data pool name.",
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        },
        "metadata_pool": {
          "description": "Name of the metadata pool.",
          "type": "string"
        },
        "metadata_pool_id": {
          "description": "Numeric id of the metadata pool.",
          "optional": 1,
          "type": "integer"
        },
        "name": {
          "description": "The ceph filesystem name.",
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
