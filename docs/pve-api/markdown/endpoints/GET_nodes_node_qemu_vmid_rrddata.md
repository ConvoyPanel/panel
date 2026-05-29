# GET /nodes/{node}/qemu/{vmid}/rrddata

Read VM RRD statistics

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

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
    "/vms/{vmid}",
    [
      "VM.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read VM RRD statistics",
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
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Audit"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "type": "array"
  }
}
```
