# GET /nodes/{node}/storage/{storage}/rrddata

Read storage RRD statistics.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| timeframe | string | yes | Specify the time frame you are interested in. |
| cf | string | no | The RRD consolidation function |

## Returns

```json
{
  "items": {
    "properties": {},
    "type": "object"
  },
  "type": "array"
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
  "description": "Read storage RRD statistics.",
  "method": "GET",
  "name": "rrddata",
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
    "items": {
      "properties": {},
      "type": "object"
    },
    "type": "array"
  }
}
```
