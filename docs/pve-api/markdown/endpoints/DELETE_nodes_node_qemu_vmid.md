# DELETE /nodes/{node}/qemu/{vmid}

Destroy the VM and  all used/owned volumes. Removes any VM specific permissions and firewall rules

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| destroy-unreferenced-disks | boolean | no | If set, destroy additionally all disks not referenced in the config but with a matching VMID from all enabled storages. |
| purge | boolean | no | Remove VMID from configurations, like backup & replication jobs and HA. |
| skiplock | boolean | no | Ignore locks - only root is allowed to use this option. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Destroy the VM and  all used/owned volumes. Removes any VM specific permissions and firewall rules",
  "method": "DELETE",
  "name": "destroy_vm",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "destroy-unreferenced-disks": {
        "default": 0,
        "description": "If set, destroy additionally all disks not referenced in the config but with a matching VMID from all enabled storages.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "purge": {
        "description": "Remove VMID from configurations, like backup & replication jobs and HA.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "skiplock": {
        "description": "Ignore locks - only root is allowed to use this option.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
        "VM.Allocate"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
