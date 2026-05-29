# GET /nodes

Cluster node index.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "cpu": {
        "description": "CPU utilization.",
        "optional": 1,
        "renderer": "fraction_as_percentage",
        "type": "number"
      },
      "level": {
        "description": "Support level.",
        "optional": 1,
        "type": "string"
      },
      "maxcpu": {
        "description": "Number of available CPUs.",
        "optional": 1,
        "type": "integer"
      },
      "maxmem": {
        "description": "Number of available memory in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "mem": {
        "description": "Used memory in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string"
      },
      "ssl_fingerprint": {
        "description": "The SSL fingerprint for the node certificate.",
        "optional": 1,
        "type": "string"
      },
      "status": {
        "description": "Node status.",
        "enum": [
          "unknown",
          "online",
          "offline"
        ],
        "type": "string"
      },
      "uptime": {
        "description": "Node uptime in seconds.",
        "optional": 1,
        "renderer": "duration",
        "type": "integer"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{node}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Cluster node index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "cpu": {
          "description": "CPU utilization.",
          "optional": 1,
          "renderer": "fraction_as_percentage",
          "type": "number"
        },
        "level": {
          "description": "Support level.",
          "optional": 1,
          "type": "string"
        },
        "maxcpu": {
          "description": "Number of available CPUs.",
          "optional": 1,
          "type": "integer"
        },
        "maxmem": {
          "description": "Number of available memory in bytes.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "mem": {
          "description": "Used memory in bytes.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "node": {
          "description": "The cluster node name.",
          "format": "pve-node",
          "type": "string"
        },
        "ssl_fingerprint": {
          "description": "The SSL fingerprint for the node certificate.",
          "optional": 1,
          "type": "string"
        },
        "status": {
          "description": "Node status.",
          "enum": [
            "unknown",
            "online",
            "offline"
          ],
          "type": "string"
        },
        "uptime": {
          "description": "Node uptime in seconds.",
          "optional": 1,
          "renderer": "duration",
          "type": "integer"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{node}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
