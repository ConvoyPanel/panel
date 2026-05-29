# DELETE /cluster/sdn/route-maps/entries/{route-map-id}/entry/{order}

Delete Route Map Entry

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| order | integer | yes | The index of this route map entry |
| route-map-id | string | yes | The SDN route map identifier |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| lock-token | string | no | the token for unlocking the global SDN configuration |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/route-maps/{route-map-id}",
    [
      "SDN.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete Route Map Entry",
  "method": "DELETE",
  "name": "delete_route_map_entry",
  "parameters": {
    "properties": {
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "order": {
        "description": "The index of this route map entry",
        "maximum": 65535,
        "minimum": 0,
        "type": "integer",
        "typetext": "<integer> (0 - 65535)"
      },
      "route-map-id": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/route-maps/{route-map-id}",
      [
        "SDN.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
