# GET /nodes/{node}/query-url-metadata

Query metadata of an URL: file size, file name and mime type.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| url | string | yes | The URL to query the metadata from. |
| verify-certificates | boolean | no | If false, no SSL/TLS certificates will be verified. |

## Returns

```json
{
  "properties": {
    "filename": {
      "optional": 1,
      "type": "string"
    },
    "mimetype": {
      "optional": 1,
      "type": "string"
    },
    "size": {
      "optional": 1,
      "renderer": "bytes",
      "type": "integer"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "or",
    [
      "perm",
      "/",
      [
        "Sys.Audit",
        "Sys.Modify"
      ]
    ],
    [
      "perm",
      "/nodes/{node}",
      [
        "Sys.AccessNetwork"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Query metadata of an URL: file size, file name and mime type.",
  "method": "GET",
  "name": "query_url_metadata",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "url": {
        "description": "The URL to query the metadata from.",
        "pattern": "https?://.*",
        "type": "string"
      },
      "verify-certificates": {
        "default": 1,
        "description": "If false, no SSL/TLS certificates will be verified.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "or",
      [
        "perm",
        "/",
        [
          "Sys.Audit",
          "Sys.Modify"
        ]
      ],
      [
        "perm",
        "/nodes/{node}",
        [
          "Sys.AccessNetwork"
        ]
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "properties": {
      "filename": {
        "optional": 1,
        "type": "string"
      },
      "mimetype": {
        "optional": 1,
        "type": "string"
      },
      "size": {
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      }
    },
    "type": "object"
  }
}
```
