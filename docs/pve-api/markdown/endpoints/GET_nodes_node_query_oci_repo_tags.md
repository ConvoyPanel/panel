# GET /nodes/{node}/query-oci-repo-tags

List all tags for an OCI repository reference.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| reference | string | yes | The reference to the repository to query tags from. |

## Returns

```json
{
  "items": {
    "type": "string"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.AccessNetwork"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List all tags for an OCI repository reference.",
  "method": "GET",
  "name": "query_oci_repo_tags",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "reference": {
        "description": "The reference to the repository to query tags from.",
        "pattern": "^(?:(?:[a-zA-Z\\d]|[a-zA-Z\\d][a-zA-Z\\d-]*[a-zA-Z\\d])(?:\\.(?:[a-zA-Z\\d]|[a-zA-Z\\d][a-zA-Z\\d-]*[a-zA-Z\\d]))*(?::\\d+)?/)?[a-z\\d]+(?:(?:[._]|__|[-]*)[a-z\\d]+)*(?:/[a-z\\d]+(?:(?:[._]|__|[-]*)[a-z\\d]+)*)*$",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.AccessNetwork"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "items": {
      "type": "string"
    },
    "type": "array"
  }
}
```
