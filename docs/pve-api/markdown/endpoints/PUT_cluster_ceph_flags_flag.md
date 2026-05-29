# PUT /cluster/ceph/flags/{flag}

Set or clear (unset) a specific Ceph flag. Runs synchronously (unlike the bulk PUT /cluster/ceph/flags endpoint, which forks a worker task).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| flag | string | yes | The ceph flag to update |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| value | boolean | yes | The new value of the flag |

## Returns

```json
{
  "type": "null"
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
  "description": "Set or clear (unset) a specific Ceph flag. Runs synchronously (unlike the bulk PUT /cluster/ceph/flags endpoint, which forks a worker task).",
  "method": "PUT",
  "name": "update_flag",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "flag": {
        "description": "The ceph flag to update",
        "enum": [
          "nobackfill",
          "nodeep-scrub",
          "nodown",
          "noin",
          "noout",
          "norebalance",
          "norecover",
          "noscrub",
          "notieragent",
          "noup",
          "pause"
        ],
        "type": "string"
      },
      "value": {
        "description": "The new value of the flag",
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
    "type": "null"
  }
}
```
