# GET /nodes/{node}/lxc/{vmid}/feature

Check if feature for virtual machine is available.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| feature | string | yes | Feature to check. |
| snapname | string | no | The name of the snapshot. |

## Returns

```json
{
  "properties": {
    "hasFeature": {
      "type": "boolean"
    }
  },
  "type": "object"
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
  "description": "Check if feature for virtual machine is available.",
  "method": "GET",
  "name": "vm_feature",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "feature": {
        "description": "Feature to check.",
        "enum": [
          "snapshot",
          "clone",
          "copy"
        ],
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
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
    "properties": {
      "hasFeature": {
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
