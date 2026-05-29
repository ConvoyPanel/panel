# GET /cluster/metrics/server

List configured metric servers.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "disable": {
        "description": "Flag to disable the plugin.",
        "type": "boolean"
      },
      "id": {
        "description": "The ID of the entry.",
        "type": "string"
      },
      "port": {
        "description": "Server network port",
        "type": "integer"
      },
      "server": {
        "description": "Server dns name or IP address",
        "type": "string"
      },
      "type": {
        "description": "Plugin type.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{id}",
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
  "description": "List configured metric servers.",
  "method": "GET",
  "name": "server_index",
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
    "items": {
      "properties": {
        "disable": {
          "description": "Flag to disable the plugin.",
          "type": "boolean"
        },
        "id": {
          "description": "The ID of the entry.",
          "type": "string"
        },
        "port": {
          "description": "Server network port",
          "type": "integer"
        },
        "server": {
          "description": "Server dns name or IP address",
          "type": "string"
        },
        "type": {
          "description": "Plugin type.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
