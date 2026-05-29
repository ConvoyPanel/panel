# POST /nodes/{node}/lxc/{vmid}/clone

Create a container clone/copy

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| newid | integer | yes | VMID for the clone. |
| bwlimit | number | no | Override I/O bandwidth limit (in KiB/s). |
| description | string | no | Description for the new CT. |
| full | boolean | no | Create a full copy of all disks. This is always done when you clone a normal CT. For CT templates, we try to create a linked clone by default. |
| hostname | string | no | Set a hostname for the new CT. |
| pool | string | no | Add the new CT to the specified pool. |
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
  "description": "You need 'VM.Clone' permissions on /vms/{vmid}, and 'VM.Allocate' permissions on /vms/{newid} (or on the VM pool /pool/{pool}). You also need 'Datastore.AllocateSpace' on any used storage, and 'SDN.Use' on any bridge."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a container clone/copy",
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
        "type": "number",
        "typetext": "<number> (0 - N)"
      },
      "description": {
        "description": "Description for the new CT.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "full": {
        "description": "Create a full copy of all disks. This is always done when you clone a normal CT. For CT templates, we try to create a linked clone by default.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "hostname": {
        "description": "Set a hostname for the new CT.",
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
        "description": "Add the new CT to the specified pool.",
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
    "description": "You need 'VM.Clone' permissions on /vms/{vmid}, and 'VM.Allocate' permissions on /vms/{newid} (or on the VM pool /pool/{pool}). You also need 'Datastore.AllocateSpace' on any used storage, and 'SDN.Use' on any bridge."
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
