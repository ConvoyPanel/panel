# DELETE /nodes/{node}/lxc/{vmid}

Destroy the container (also delete all uses files).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| destroy-unreferenced-disks | boolean | no | If set, destroy additionally all disks with the VMID from all enabled storages which are not referenced in the config. |
| force | boolean | no | Force destroy, even if running. |
| purge | boolean | no | Remove container from all related configurations. For example, backup jobs, replication jobs or HA. Related ACLs and Firewall entries will *always* be removed. |

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
  "description": "Destroy the container (also delete all uses files).",
  "method": "DELETE",
  "name": "destroy_vm",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "destroy-unreferenced-disks": {
        "description": "If set, destroy additionally all disks with the VMID from all enabled storages which are not referenced in the config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "force": {
        "default": 0,
        "description": "Force destroy, even if running.",
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
        "default": 0,
        "description": "Remove container from all related configurations. For example, backup jobs, replication jobs or HA. Related ACLs and Firewall entries will *always* be removed.",
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
