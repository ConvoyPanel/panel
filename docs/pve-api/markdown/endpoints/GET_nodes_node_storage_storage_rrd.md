# GET /nodes/{node}/storage/{storage}/rrd

Read storage RRD statistics (returns PNG).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

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
    "/storage/{storage}",
    [
      "Datastore.Audit",
      "Datastore.AllocateSpace"
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
  "description": "Read storage RRD statistics (returns PNG).",
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
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "timeframe": {
        "description": "Specify the time frame you are interested in.",
        "enum": [
          "hour",
          "day",
          "week",
          "month",
          "year"
        ],
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.Audit",
        "Datastore.AllocateSpace"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
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
