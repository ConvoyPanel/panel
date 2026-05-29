# GET /nodes/{node}/storage/{storage}/content

List storage content.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| content | string | no | Only list content of this type. |
| vmid | integer | no | Only list images for this VM |

## Returns

```json
{
  "items": {
    "properties": {
      "approximate-size": {
        "description": "Approximate volume size in bytes. Present instead of 'size' for storages where determining the exact size has technical limitations. Will typically be an upper bound on the actual size, but the exact semantics depend on the storage plugin.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "ctime": {
        "description": "Creation time (seconds since the UNIX Epoch).",
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "encrypted": {
        "description": "If whole backup is encrypted, value is the fingerprint or '1'  if encrypted. Only useful for the Proxmox Backup Server storage type.",
        "optional": 1,
        "type": "string"
      },
      "format": {
        "description": "Format identifier ('raw', 'qcow2', 'subvol', 'iso', 'tgz' ...)",
        "type": "string"
      },
      "notes": {
        "description": "Optional notes. If they contain multiple lines, only the first one is returned here.",
        "optional": 1,
        "type": "string"
      },
      "parent": {
        "description": "Volume identifier of parent (for linked cloned).",
        "optional": 1,
        "type": "string"
      },
      "protected": {
        "description": "Protection status. Currently only supported for backups.",
        "optional": 1,
        "type": "boolean"
      },
      "size": {
        "description": "Volume size in bytes.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "used": {
        "description": "Used space. Please note that most storage plugins do not report anything useful here.",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "verification": {
        "description": "Last backup verification result, only useful for PBS storages.",
        "optional": 1,
        "properties": {
          "state": {
            "description": "Last backup verification state.",
            "type": "string"
          },
          "upid": {
            "description": "Last backup verification UPID.",
            "type": "string"
          }
        },
        "type": "object"
      },
      "vmid": {
        "description": "Associated Owner VMID.",
        "optional": 1,
        "type": "integer"
      },
      "volid": {
        "description": "Volume identifier.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{volid}",
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
    "/storage/{storage}",
    [
      "Datastore.Audit",
      "Datastore.AllocateSpace"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List storage content.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "content": {
        "description": "Only list content of this type.",
        "format": "pve-storage-content",
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
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "vmid": {
        "description": "Only list images for this VM",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.Audit",
        "Datastore.AllocateSpace"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "approximate-size": {
          "description": "Approximate volume size in bytes. Present instead of 'size' for storages where determining the exact size has technical limitations. Will typically be an upper bound on the actual size, but the exact semantics depend on the storage plugin.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "ctime": {
          "description": "Creation time (seconds since the UNIX Epoch).",
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        },
        "encrypted": {
          "description": "If whole backup is encrypted, value is the fingerprint or '1'  if encrypted. Only useful for the Proxmox Backup Server storage type.",
          "optional": 1,
          "type": "string"
        },
        "format": {
          "description": "Format identifier ('raw', 'qcow2', 'subvol', 'iso', 'tgz' ...)",
          "type": "string"
        },
        "notes": {
          "description": "Optional notes. If they contain multiple lines, only the first one is returned here.",
          "optional": 1,
          "type": "string"
        },
        "parent": {
          "description": "Volume identifier of parent (for linked cloned).",
          "optional": 1,
          "type": "string"
        },
        "protected": {
          "description": "Protection status. Currently only supported for backups.",
          "optional": 1,
          "type": "boolean"
        },
        "size": {
          "description": "Volume size in bytes.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "used": {
          "description": "Used space. Please note that most storage plugins do not report anything useful here.",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "verification": {
          "description": "Last backup verification result, only useful for PBS storages.",
          "optional": 1,
          "properties": {
            "state": {
              "description": "Last backup verification state.",
              "type": "string"
            },
            "upid": {
              "description": "Last backup verification UPID.",
              "type": "string"
            }
          },
          "type": "object"
        },
        "vmid": {
          "description": "Associated Owner VMID.",
          "optional": 1,
          "type": "integer"
        },
        "volid": {
          "description": "Volume identifier.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{volid}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
