# GET /nodes/{node}/replication

List status of all replication jobs on this node.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| guest | integer | no | Only list replication jobs for this guest. |

## Returns

```json
{
  "items": {
    "properties": {
      "id": {
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
  "description": "Requires the VM.Audit permission on /vms/<vmid>.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List status of all replication jobs on this node.",
  "method": "GET",
  "name": "status",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "guest": {
        "description": "Only list replication jobs for this guest.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Requires the VM.Audit permission on /vms/<vmid>.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "id": {
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
