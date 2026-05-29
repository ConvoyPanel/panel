# GET /cluster/ceph/flags

get the status of all ceph flags

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "additionalProperties": 1,
    "properties": {
      "description": {
        "description": "Flag description.",
        "type": "string"
      },
      "name": {
        "description": "Flag name.",
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
        "description": "Flag value.",
        "type": "boolean"
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
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "get the status of all ceph flags",
  "method": "GET",
  "name": "get_all_flags",
  "parameters": {
    "additionalProperties": 0
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
    "items": {
      "additionalProperties": 1,
      "properties": {
        "description": {
          "description": "Flag description.",
          "type": "string"
        },
        "name": {
          "description": "Flag name.",
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
          "description": "Flag value.",
          "type": "boolean"
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
