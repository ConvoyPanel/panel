# GET /cluster/options

Get datacenter options. Without 'Sys.Audit' on '/' not all options are returned.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
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
  ],
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get datacenter options. Without 'Sys.Audit' on '/' not all options are returned.",
  "method": "GET",
  "name": "get_options",
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
    ],
    "user": "all"
  },
  "returns": {
    "type": "object"
  }
}
```
