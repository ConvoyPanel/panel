# GET /nodes/{node}/tasks/{upid}

upid_index

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| upid | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {},
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "",
  "method": "GET",
  "name": "upid_index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "upid": {
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {},
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
