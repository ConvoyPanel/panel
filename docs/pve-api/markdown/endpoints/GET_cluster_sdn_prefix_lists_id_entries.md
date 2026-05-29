# GET /cluster/sdn/prefix-lists/{id}/entries

List Prefix List Entries

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The SDN prefix list identifier |

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
      "href": "{seq}",
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
  "description": "List Prefix List Entries",
  "method": "GET",
  "name": "get_prefix_list_entries",
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
    "items": {
      "properties": {},
      "type": "object"
    },
    "links": [
      {
        "href": "{seq}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
