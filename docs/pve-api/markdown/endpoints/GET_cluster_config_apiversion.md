# GET /cluster/config/apiversion

Return the version of the cluster join API available on this node.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "description": "Cluster Join API version, currently 1",
  "minimum": 0,
  "type": "integer"
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
  "description": "Return the version of the cluster join API available on this node.",
  "method": "GET",
  "name": "join_api_version",
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
  "returns": {
    "description": "Cluster Join API version, currently 1",
    "minimum": 0,
    "type": "integer"
  }
}
```
