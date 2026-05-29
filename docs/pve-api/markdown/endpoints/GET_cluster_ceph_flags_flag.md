# GET /cluster/ceph/flags/{flag}

Get the status of a specific ceph flag.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| flag | string | yes | The name of the flag name to get. |

## Request parameters

None.

## Returns

```json
{
  "type": "boolean"
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
  "description": "Get the status of a specific ceph flag.",
  "method": "GET",
  "name": "get_flag",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "flag": {
        "description": "The name of the flag name to get.",
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
  "protected": 1,
  "returns": {
    "type": "boolean"
  }
}
```
