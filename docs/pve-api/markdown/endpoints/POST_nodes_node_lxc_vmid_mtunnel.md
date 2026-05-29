# POST /nodes/{node}/lxc/{vmid}/mtunnel

Migration tunnel endpoint - only for internal use by CT migration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| bridges | string | no | List of network bridges to check availability. Will be checked again for actually used bridges during migration. |
| storages | string | no | List of storages to check permission and availability. Will be checked again for all actually used storages during migration. |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "socket": {
      "type": "string"
    },
    "ticket": {
      "type": "string"
    },
    "upid": {
      "type": "string"
    }
  }
}
```

## Permissions

```json
{
  "check": [
    "and",
    [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Allocate"
      ]
    ],
    [
      "perm",
      "/",
      [
        "Sys.Incoming"
      ]
    ]
  ],
  "description": "You need 'VM.Allocate' permissions on '/vms/{vmid}' and Sys.Incoming on '/'. Further permission checks happen during the actual migration."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Migration tunnel endpoint - only for internal use by CT migration.",
  "method": "POST",
  "name": "mtunnel",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "bridges": {
        "description": "List of network bridges to check availability. Will be checked again for actually used bridges during migration.",
        "format": "pve-bridge-id-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storages": {
        "description": "List of storages to check permission and availability. Will be checked again for all actually used storages during migration.",
        "format": "pve-storage-id-list",
        "optional": 1,
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
      "and",
      [
        "perm",
        "/vms/{vmid}",
        [
          "VM.Allocate"
        ]
      ],
      [
        "perm",
        "/",
        [
          "Sys.Incoming"
        ]
      ]
    ],
    "description": "You need 'VM.Allocate' permissions on '/vms/{vmid}' and Sys.Incoming on '/'. Further permission checks happen during the actual migration."
  },
  "protected": 1,
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "socket": {
        "type": "string"
      },
      "ticket": {
        "type": "string"
      },
      "upid": {
        "type": "string"
      }
    }
  }
}
```
