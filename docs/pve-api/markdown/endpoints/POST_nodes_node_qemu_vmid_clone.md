# POST /nodes/{node}/qemu/{vmid}/clone

Create a copy of virtual machine/template.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| newid | integer | yes | VMID for the clone. |
| bwlimit | integer | no | Override I/O bandwidth limit (in KiB/s). |
| description | string | no | Description for the new VM. |
| format | string | no | Target format for file storage. Only valid for full clone. |
| full | boolean | no | Create a full copy of all disks. This is always done when you clone a normal VM. For VM templates, we try to create a linked clone by default. |
| name | string | no | Set a name for the new VM. |
| pool | string | no | Add the new VM to the specified pool. |
| snapname | string | no | The name of the snapshot. |
| storage | string | no | Target storage for full clone. |
| target | string | no | Target node. Only allowed if the original VM is on shared storage. |

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
    "and",
    [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Clone"
      ]
    ],
    [
      "or",
      [
        "perm",
        "/vms/{newid}",
        [
          "VM.Allocate"
        ]
      ],
      [
        "perm",
        "/pool/{pool}",
        [
          "VM.Allocate"
        ],
        "require_param",
        "pool"
      ]
    ]
  ],
  "description": "You need 'VM.Clone' permissions on /vms/{vmid}, and 'VM.Allocate' permissions on /vms/{newid} (or on the VM pool /pool/{pool}). You also need 'Datastore.AllocateSpace' on any used storage and 'SDN.Use' on any used bridge/vnet"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a copy of virtual machine/template.",
  "method": "POST",
  "name": "clone_vm",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "bwlimit": {
        "default": "clone limit from datacenter or storage config",
        "description": "Override I/O bandwidth limit (in KiB/s).",
        "minimum": "0",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "description": {
        "description": "Description for the new VM.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "format": {
        "description": "Target format for file storage. Only valid for full clone.",
        "enum": [
          "raw",
          "qcow2",
          "vmdk"
        ],
        "optional": 1,
        "type": "string"
      },
      "full": {
        "description": "Create a full copy of all disks. This is always done when you clone a normal VM. For VM templates, we try to create a linked clone by default.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "name": {
        "description": "Set a name for the new VM.",
        "format": "dns-name",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "newid": {
        "description": "VMID for the clone.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pool": {
        "description": "Add the new VM to the specified pool.",
        "format": "pve-poolid",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "snapname": {
        "description": "The name of the snapshot.",
        "format": "pve-configid",
        "maxLength": 40,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "Target storage for full clone.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string",
        "typetext": "<storage ID>"
      },
      "target": {
        "description": "Target node. Only allowed if the original VM is on shared storage.",
        "format": "pve-node",
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
          "VM.Clone"
        ]
      ],
      [
        "or",
        [
          "perm",
          "/vms/{newid}",
          [
            "VM.Allocate"
          ]
        ],
        [
          "perm",
          "/pool/{pool}",
          [
            "VM.Allocate"
          ],
          "require_param",
          "pool"
        ]
      ]
    ],
    "description": "You need 'VM.Clone' permissions on /vms/{vmid}, and 'VM.Allocate' permissions on /vms/{newid} (or on the VM pool /pool/{pool}). You also need 'Datastore.AllocateSpace' on any used storage and 'SDN.Use' on any used bridge/vnet"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
