# GET /nodes/{node}/rrd

Read node RRD statistics (returns PNG)

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| ds | string | yes | The list of datasources you want to display. |
| timeframe | string | yes | Specify the time frame you are interested in. |
| cf | string | no | The RRD consolidation function |

## Returns

```json
{
  "properties": {
    "filename": {
      "type": "string"
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
    "/nodes/{node}",
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
  "description": "Read node RRD statistics (returns PNG)",
  "method": "GET",
  "name": "rrd",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cf": {
        "description": "The RRD consolidation function",
        "enum": [
          "AVERAGE",
          "MAX"
        ],
        "optional": 1,
        "type": "string"
      },
      "ds": {
        "description": "The list of datasources you want to display.",
        "format": "pve-configid-list",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "timeframe": {
        "description": "Specify the time frame you are interested in.",
        "enum": [
          "hour",
          "day",
          "week",
          "month",
          "year",
          "decade"
        ],
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "properties": {
      "filename": {
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
