# GET /cluster/metrics/export

Retrieve metrics of the cluster.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| history | boolean | no | Also return historic values. Returns full available metric history unless `start-time` is also set |
| local-only | boolean | no | Only return metrics for the current node instead of the whole cluster |
| node-list | string | no | Only return metrics from nodes passed as comma-separated list |
| start-time | integer | no | Only include metrics with a timestamp > start-time. |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "data": {
      "description": "Array of system metrics. Metrics are sorted by their timestamp.",
      "items": {
        "additionalProperties": 0,
        "properties": {
          "id": {
            "description": "Unique identifier for this metric object, for instance 'node/<nodename>' or 'qemu/<vmid>'.",
            "type": "string"
          },
          "metric": {
            "description": "Name of the metric.",
            "type": "string"
          },
          "timestamp": {
            "description": "Time at which this metric was observed",
            "type": "integer"
          },
          "type": {
            "description": "Type of the metric.",
            "enum": [
              "gauge",
              "counter",
              "derive"
            ],
            "type": "string"
          },
          "value": {
            "description": "Metric value.",
            "type": "number"
          }
        },
        "type": "object"
      },
      "type": "array"
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
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Retrieve metrics of the cluster.",
  "expose_credentials": 1,
  "method": "GET",
  "name": "export",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "history": {
        "default": 0,
        "description": "Also return historic values. Returns full available metric history unless `start-time` is also set",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "local-only": {
        "default": 0,
        "description": "Only return metrics for the current node instead of the whole cluster",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node-list": {
        "description": "Only return metrics from nodes passed as comma-separated list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "start-time": {
        "default": 0,
        "description": "Only include metrics with a timestamp > start-time.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "data": {
        "description": "Array of system metrics. Metrics are sorted by their timestamp.",
        "items": {
          "additionalProperties": 0,
          "properties": {
            "id": {
              "description": "Unique identifier for this metric object, for instance 'node/<nodename>' or 'qemu/<vmid>'.",
              "type": "string"
            },
            "metric": {
              "description": "Name of the metric.",
              "type": "string"
            },
            "timestamp": {
              "description": "Time at which this metric was observed",
              "type": "integer"
            },
            "type": {
              "description": "Type of the metric.",
              "enum": [
                "gauge",
                "counter",
                "derive"
              ],
              "type": "string"
            },
            "value": {
              "description": "Metric value.",
              "type": "number"
            }
          },
          "type": "object"
        },
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
