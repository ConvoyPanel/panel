# POST /nodes/{node}/suspendall

Suspend all VMs.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| max-workers | integer | no | Maximal number of parallel migration job. If not set, uses'max_workers' from datacenter.cfg, and if that's not set the available'                     .' CPU threads, clamped to a maximum of 8, are used. |
| vms | string | no | Only consider Guests with these IDs. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter. Additionally, you need 'VM.Config.Disk' on the '/vms/{vmid}' path and 'Datastore.AllocateSpace' for the configured state-storage(s)",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Suspend all VMs.",
  "method": "POST",
  "name": "suspendall",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "max-workers": {
        "description": "Maximal number of parallel migration job. If not set, uses'max_workers' from datacenter.cfg, and if that's not set the available'\n                    .' CPU threads, clamped to a maximum of 8, are used.",
        "maximum": 64,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 64)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vms": {
        "description": "Only consider Guests with these IDs.",
        "format": "pve-vmid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "The 'VM.PowerMgmt' permission is required on '/' or on '/vms/<ID>' for each ID passed via the 'vms' parameter. Additionally, you need 'VM.Config.Disk' on the '/vms/{vmid}' path and 'Datastore.AllocateSpace' for the configured state-storage(s)",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
