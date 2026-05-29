# GET /cluster/sdn/prefix-lists/{id}/entries/{url_seq}

Get Prefix List Entry

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The SDN prefix list identifier |

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
    "/sdn/prefix-lists/{id}",
    [
      "SDN.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get Prefix List Entry",
  "method": "GET",
  "name": "get_prefix_list_entry",
  "parameters": {
    "properties": {
      "id": {
        "description": "The SDN prefix list identifier",
        "format": "pve-sdn-prefix-list-id",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/prefix-lists/{id}",
      [
        "SDN.Audit"
      ]
    ]
  },
  "returns": {
    "type": "object"
  }
}
```
