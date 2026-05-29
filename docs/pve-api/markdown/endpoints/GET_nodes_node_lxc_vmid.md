# GET /nodes/{node}/lxc/{vmid}

Directory index

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "subdir": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{subdir}",
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
  "description": "Directory index",
  "method": "GET",
  "name": "vmdiridx",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "subdir": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{subdir}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
