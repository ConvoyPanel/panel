# GET /access/roles/{roleid}

Get role configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| roleid | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "Datastore.Allocate": {
      "optional": 1,
      "type": "boolean"
    },
    "Datastore.AllocateSpace": {
      "optional": 1,
      "type": "boolean"
    },
    "Datastore.AllocateTemplate": {
      "optional": 1,
      "type": "boolean"
    },
    "Datastore.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "Group.Allocate": {
      "optional": 1,
      "type": "boolean"
    },
    "Mapping.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "Mapping.Modify": {
      "optional": 1,
      "type": "boolean"
    },
    "Mapping.Use": {
      "optional": 1,
      "type": "boolean"
    },
    "Permissions.Modify": {
      "optional": 1,
      "type": "boolean"
    },
    "Pool.Allocate": {
      "optional": 1,
      "type": "boolean"
    },
    "Pool.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "Realm.Allocate": {
      "optional": 1,
      "type": "boolean"
    },
    "Realm.AllocateUser": {
      "optional": 1,
      "type": "boolean"
    },
    "SDN.Allocate": {
      "optional": 1,
      "type": "boolean"
    },
    "SDN.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "SDN.Use": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.AccessNetwork": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.Console": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.Incoming": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.Modify": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.PowerMgmt": {
      "optional": 1,
      "type": "boolean"
    },
    "Sys.Syslog": {
      "optional": 1,
      "type": "boolean"
    },
    "User.Modify": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Allocate": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Backup": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Clone": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.CDROM": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.CPU": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.Cloudinit": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.Disk": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.HWType": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.Memory": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.Network": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Config.Options": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Console": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.GuestAgent.Audit": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.GuestAgent.FileRead": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.GuestAgent.FileSystemMgmt": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.GuestAgent.FileWrite": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.GuestAgent.Unrestricted": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Migrate": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.PowerMgmt": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Replicate": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Snapshot": {
      "optional": 1,
      "type": "boolean"
    },
    "VM.Snapshot.Rollback": {
      "optional": 1,
      "type": "boolean"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get role configuration.",
  "method": "GET",
  "name": "read_role",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "roleid": {
        "format": "pve-roleid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "Datastore.Allocate": {
        "optional": 1,
        "type": "boolean"
      },
      "Datastore.AllocateSpace": {
        "optional": 1,
        "type": "boolean"
      },
      "Datastore.AllocateTemplate": {
        "optional": 1,
        "type": "boolean"
      },
      "Datastore.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "Group.Allocate": {
        "optional": 1,
        "type": "boolean"
      },
      "Mapping.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "Mapping.Modify": {
        "optional": 1,
        "type": "boolean"
      },
      "Mapping.Use": {
        "optional": 1,
        "type": "boolean"
      },
      "Permissions.Modify": {
        "optional": 1,
        "type": "boolean"
      },
      "Pool.Allocate": {
        "optional": 1,
        "type": "boolean"
      },
      "Pool.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "Realm.Allocate": {
        "optional": 1,
        "type": "boolean"
      },
      "Realm.AllocateUser": {
        "optional": 1,
        "type": "boolean"
      },
      "SDN.Allocate": {
        "optional": 1,
        "type": "boolean"
      },
      "SDN.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "SDN.Use": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.AccessNetwork": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.Console": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.Incoming": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.Modify": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.PowerMgmt": {
        "optional": 1,
        "type": "boolean"
      },
      "Sys.Syslog": {
        "optional": 1,
        "type": "boolean"
      },
      "User.Modify": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Allocate": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Backup": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Clone": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.CDROM": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.CPU": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.Cloudinit": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.Disk": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.HWType": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.Memory": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.Network": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Config.Options": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Console": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.GuestAgent.Audit": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.GuestAgent.FileRead": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.GuestAgent.FileSystemMgmt": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.GuestAgent.FileWrite": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.GuestAgent.Unrestricted": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Migrate": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.PowerMgmt": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Replicate": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Snapshot": {
        "optional": 1,
        "type": "boolean"
      },
      "VM.Snapshot.Rollback": {
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
