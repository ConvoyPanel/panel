# PUT /cluster/ceph/flags

Set/Unset multiple Ceph flags at once. Each flag is a top-level optional boolean: passing true sets the flag, false unsets it, omitting it leaves the current state untouched. Runs as a worker task; returns a UPID to follow.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| nobackfill | boolean | no | Backfilling of PGs is suspended. |
| nodeep-scrub | boolean | no | Deep Scrubbing is disabled. |
| nodown | boolean | no | OSD failure reports are being ignored, such that the monitors will not mark OSDs down. |
| noin | boolean | no | OSDs that were previously marked out will not be marked back in when they start. |
| noout | boolean | no | OSDs will not automatically be marked out after the configured interval. |
| norebalance | boolean | no | Rebalancing of PGs is suspended. |
| norecover | boolean | no | Recovery of PGs is suspended. |
| noscrub | boolean | no | Scrubbing is disabled. |
| notieragent | boolean | no | Cache tiering activity is suspended. |
| noup | boolean | no | OSDs are not allowed to start. |
| pause | boolean | no | Pauses read and writes. |

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
  "description": "Set/Unset multiple Ceph flags at once. Each flag is a top-level optional boolean: passing true sets the flag, false unsets it, omitting it leaves the current state untouched. Runs as a worker task; returns a UPID to follow.",
  "method": "PUT",
  "name": "set_flags",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "nobackfill": {
        "description": "Backfilling of PGs is suspended.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "nodeep-scrub": {
        "description": "Deep Scrubbing is disabled.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "nodown": {
        "description": "OSD failure reports are being ignored, such that the monitors will not mark OSDs down.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "noin": {
        "description": "OSDs that were previously marked out will not be marked back in when they start.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "noout": {
        "description": "OSDs will not automatically be marked out after the configured interval.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "norebalance": {
        "description": "Rebalancing of PGs is suspended.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "norecover": {
        "description": "Recovery of PGs is suspended.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "noscrub": {
        "description": "Scrubbing is disabled.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "notieragent": {
        "description": "Cache tiering activity is suspended.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "noup": {
        "description": "OSDs are not allowed to start.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "pause": {
        "description": "Pauses read and writes.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
  "returns": {
    "type": "string"
  }
}
```
