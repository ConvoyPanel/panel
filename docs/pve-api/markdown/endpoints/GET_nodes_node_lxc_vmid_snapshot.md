# GET /nodes/{node}/lxc/{vmid}/snapshot

List all snapshots.

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
      "description": {
        "description": "Snapshot description.",
        "type": "string"
      },
      "name": {
        "description": "Snapshot identifier. Value 'current' identifies the current VM.",
        "type": "string"
      },
      "parent": {
        "description": "Parent snapshot identifier.",
        "optional": 1,
        "type": "string"
      },
      "snaptime": {
        "description": "Snapshot creation time",
        "optional": 1,
        "renderer": "timestamp",
        "type": "integer"
      }
    },
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
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List all snapshots.",
  "method": "GET",
  "name": "list",
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
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "description": {
          "description": "Snapshot description.",
          "type": "string"
        },
        "name": {
          "description": "Snapshot identifier. Value 'current' identifies the current VM.",
          "type": "string"
        },
        "parent": {
          "description": "Parent snapshot identifier.",
          "optional": 1,
          "type": "string"
        },
        "snaptime": {
          "description": "Snapshot creation time",
          "optional": 1,
          "renderer": "timestamp",
          "type": "integer"
        }
      },
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
