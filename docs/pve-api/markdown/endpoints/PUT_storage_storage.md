# PUT /storage/{storage}

Update storage configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| blocksize | string | no | ZFS block size |
| bwlimit | string | no | Set I/O bandwidth limit for various operations (in KiB/s). |
| comstar_hg | string | no | host group for comstar views |
| comstar_tg | string | no | target group for comstar views |
| content | string | no | Allowed content types.  NOTE: the value 'rootdir' is used for Containers, and value 'images' for VMs. |
| content-dirs | string | no | Overrides for default content type directories. |
| create-base-path | boolean | no | Create the base directory if it doesn't exist. |
| create-subdirs | boolean | no | Populate the directory with the default structure. |
| data-pool | string | no | Data Pool (for erasure coding only) |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable | boolean | no | Flag to disable the storage. |
| domain | string | no | CIFS domain. |
| encryption-key | string | no | Encryption key. Use 'autogen' to generate one automatically without passphrase. |
| fingerprint | string | no | Certificate SHA 256 fingerprint. |
| format | string | no | Default image format. |
| fs-name | string | no | The Ceph filesystem name. |
| fuse | boolean | no | Mount CephFS through FUSE. |
| is_mountpoint | string | no | Assume the given path is an externally managed mountpoint and consider the storage offline if it is not mounted. Using a boolean (yes/no) value serves as a shortcut to using the target path in this field. |
| keyring | string | no | Client keyring contents (for external clusters). |
| krbd | boolean | no | Always access rbd through krbd kernel module. |
| lio_tpg | string | no | target portal group for Linux LIO targets |
| master-pubkey | string | no | Base64-encoded, PEM-formatted public RSA key. Used to encrypt a copy of the encryption-key which will be added to each encrypted backup. |
| max-protected-backups | integer | no | Maximal number of protected backups per guest. Use '-1' for unlimited. |
| mkdir | boolean | no | Create the directory if it doesn't exist and populate it with default sub-dirs. NOTE: Deprecated, use the 'create-base-path' and 'create-subdirs' options instead. |
| monhost | string | no | IP addresses of monitors (for external clusters). |
| mountpoint | string | no | mount point |
| namespace | string | no | Namespace. |
| nocow | boolean | no | Set the NOCOW flag on files. Disables data checksumming and causes data errors to be unrecoverable from while allowing direct I/O. Only use this if data does not need to be any more safe than on a single ext4 formatted disk with no underlying raid system. |
| nodes | string | no | List of nodes for which the storage configuration applies. |
| nowritecache | boolean | no | disable write caching on the target |
| options | string | no | NFS/CIFS mount options (see 'man nfs' or 'man mount.cifs') |
| password | string | no | Password for accessing the share/datastore. |
| pool | string | no | Pool. |
| port | integer | no | Use this port to connect to the storage instead of the default one (for example, with PBS or ESXi). For NFS and CIFS, use the 'options' option to configure the port via the mount options. |
| preallocation | string | no | Preallocation mode for raw and qcow2 images. Using 'metadata' on raw images results in preallocation=off. |
| prune-backups | string | no | The retention options with shorter intervals are processed first with --keep-last being the very first one. Each option covers a specific period of time. We say that backups within this period are covered by this option. The next option does not take care of already covered backups and only considers older backups. |
| saferemove | boolean | no | Zero-out data when removing LVs. |
| saferemove_throughput | string | no | Wipe throughput (cstream -t parameter value). |
| saferemove-stepsize | integer | no | Wipe step size in MiB. It will be capped to the maximum supported by the storage. |
| server | string | no | Server IP or DNS name. |
| shared | boolean | no | Indicate that this is a single storage with the same contents on all nodes (or all listed in the 'nodes' option). It will not make the contents of a local storage automatically accessible to other nodes, it just marks an already shared storage as such! |
| skip-cert-verification | boolean | no | Disable TLS certificate verification, only enable on fully trusted networks! |
| smbversion | string | no | SMB protocol version. 'default' if not set, negotiates the highest SMB2+ version supported by both the client and server. |
| snapshot-as-volume-chain | boolean | no | Enable support for creating storage-vendor agnostic snapshot through volume backing-chains. |
| sparse | boolean | no | use sparse volumes |
| subdir | string | no | Subdir to mount. |
| tagged_only | boolean | no | Only list logical volumes tagged with 'pve-vm-ID'. |
| username | string | no | RBD Id. |
| zfs-base-path | string | no | Base path where to look for the created ZFS block devices. Set automatically during creation if not specified. Usually '/dev/zvol'. |

## Returns

```json
{
  "properties": {
    "config": {
      "additionalProperties": 1,
      "description": "Partial, possibly server generated, configuration properties.",
      "optional": 1,
      "properties": {
        "encryption-key": {
          "description": "The, possibly auto-generated, encryption-key.",
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "storage": {
      "description": "The ID of the created storage.",
      "type": "string"
    },
    "type": {
      "description": "The type of the created storage.",
      "enum": [
        "btrfs",
        "cephfs",
        "cifs",
        "dir",
        "esxi",
        "iscsi",
        "iscsidirect",
        "lvm",
        "lvmthin",
        "nfs",
        "pbs",
        "rbd",
        "zfs",
        "zfspool"
      ],
      "type": "string"
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
    "/storage",
    [
      "Datastore.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update storage configuration.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "blocksize": {
        "description": "ZFS block size",
        "format": "pve-storage-zfs-blocksize",
        "format_description": "a power of 2 with optional k or m suffix",
        "optional": 1,
        "type": "string",
        "typetext": "<a power of 2 with optional k or m suffix>"
      },
      "bwlimit": {
        "description": "Set I/O bandwidth limit for various operations (in KiB/s).",
        "format": {
          "clone": {
            "description": "bandwidth limit in KiB/s for cloning disks",
            "format_description": "LIMIT",
            "minimum": "0",
            "optional": 1,
            "type": "number"
          },
          "default": {
            "description": "default bandwidth limit in KiB/s",
            "format_description": "LIMIT",
            "minimum": "0",
            "optional": 1,
            "type": "number"
          },
          "migration": {
            "description": "bandwidth limit in KiB/s for migrating guests (including moving local disks)",
            "format_description": "LIMIT",
            "minimum": "0",
            "optional": 1,
            "type": "number"
          },
          "move": {
            "description": "bandwidth limit in KiB/s for moving disks",
            "format_description": "LIMIT",
            "minimum": "0",
            "optional": 1,
            "type": "number"
          },
          "restore": {
            "description": "bandwidth limit in KiB/s for restoring guests from backups",
            "format_description": "LIMIT",
            "minimum": "0",
            "optional": 1,
            "type": "number"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[clone=<LIMIT>] [,default=<LIMIT>] [,migration=<LIMIT>] [,move=<LIMIT>] [,restore=<LIMIT>]"
      },
      "comstar_hg": {
        "description": "host group for comstar views",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "comstar_tg": {
        "description": "target group for comstar views",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "content": {
        "description": "Allowed content types.\n\nNOTE: the value 'rootdir' is used for Containers, and value 'images' for VMs.\n",
        "format": "pve-storage-content-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "content-dirs": {
        "description": "Overrides for default content type directories.",
        "format": "pve-dir-override-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "create-base-path": {
        "default": "yes",
        "description": "Create the base directory if it doesn't exist.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "create-subdirs": {
        "default": "yes",
        "description": "Populate the directory with the default structure.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "data-pool": {
        "description": "Data Pool (for erasure coding only)",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "disable": {
        "description": "Flag to disable the storage.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "domain": {
        "description": "CIFS domain.",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "encryption-key": {
        "description": "Encryption key. Use 'autogen' to generate one automatically without passphrase.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "fingerprint": {
        "description": "Certificate SHA 256 fingerprint.",
        "optional": 1,
        "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
        "type": "string"
      },
      "format": {
        "description": "Default image format.",
        "enum": [
          "raw",
          "qcow2",
          "subvol",
          "vmdk"
        ],
        "optional": 1,
        "type": "string"
      },
      "fs-name": {
        "description": "The Ceph filesystem name.",
        "format": "pve-configid",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "fuse": {
        "description": "Mount CephFS through FUSE.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "is_mountpoint": {
        "default": "no",
        "description": "Assume the given path is an externally managed mountpoint and consider the storage offline if it is not mounted. Using a boolean (yes/no) value serves as a shortcut to using the target path in this field.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "keyring": {
        "description": "Client keyring contents (for external clusters).",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "krbd": {
        "default": 0,
        "description": "Always access rbd through krbd kernel module.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "lio_tpg": {
        "description": "target portal group for Linux LIO targets",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "master-pubkey": {
        "description": "Base64-encoded, PEM-formatted public RSA key. Used to encrypt a copy of the encryption-key which will be added to each encrypted backup.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "max-protected-backups": {
        "default": "Unlimited for users with Datastore.Allocate privilege, 5 for other users",
        "description": "Maximal number of protected backups per guest. Use '-1' for unlimited.",
        "minimum": -1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (-1 - N)"
      },
      "mkdir": {
        "default": "yes",
        "description": "Create the directory if it doesn't exist and populate it with default sub-dirs. NOTE: Deprecated, use the 'create-base-path' and 'create-subdirs' options instead.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "monhost": {
        "description": "IP addresses of monitors (for external clusters).",
        "format": "pve-storage-portal-dns-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "mountpoint": {
        "description": "mount point",
        "format": "pve-storage-path",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "namespace": {
        "description": "Namespace.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "nocow": {
        "default": 0,
        "description": "Set the NOCOW flag on files. Disables data checksumming and causes data errors to be unrecoverable from while allowing direct I/O. Only use this if data does not need to be any more safe than on a single ext4 formatted disk with no underlying raid system.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "nodes": {
        "description": "List of nodes for which the storage configuration applies.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "nowritecache": {
        "description": "disable write caching on the target",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "options": {
        "description": "NFS/CIFS mount options (see 'man nfs' or 'man mount.cifs')",
        "format": "pve-storage-options",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "Password for accessing the share/datastore.",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "pool": {
        "description": "Pool.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "description": "Use this port to connect to the storage instead of the default one (for example, with PBS or ESXi). For NFS and CIFS, use the 'options' option to configure the port via the mount options.",
        "maximum": 65535,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 65535)"
      },
      "preallocation": {
        "default": "metadata",
        "description": "Preallocation mode for raw and qcow2 images. Using 'metadata' on raw images results in preallocation=off.",
        "enum": [
          "off",
          "metadata",
          "falloc",
          "full"
        ],
        "optional": 1,
        "type": "string"
      },
      "prune-backups": {
        "description": "The retention options with shorter intervals are processed first with --keep-last being the very first one. Each option covers a specific period of time. We say that backups within this period are covered by this option. The next option does not take care of already covered backups and only considers older backups.",
        "format": "prune-backups",
        "optional": 1,
        "type": "string",
        "typetext": "[keep-all=<1|0>] [,keep-daily=<N>] [,keep-hourly=<N>] [,keep-last=<N>] [,keep-monthly=<N>] [,keep-weekly=<N>] [,keep-yearly=<N>]"
      },
      "saferemove": {
        "description": "Zero-out data when removing LVs.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "saferemove-stepsize": {
        "default": 32,
        "description": "Wipe step size in MiB. It will be capped to the maximum supported by the storage.",
        "enum": [
          "1",
          "2",
          "4",
          "8",
          "16",
          "32"
        ],
        "optional": 1,
        "type": "integer"
      },
      "saferemove_throughput": {
        "description": "Wipe throughput (cstream -t parameter value).",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "server": {
        "description": "Server IP or DNS name.",
        "format": "pve-storage-server",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "shared": {
        "description": "Indicate that this is a single storage with the same contents on all nodes (or all listed in the 'nodes' option). It will not make the contents of a local storage automatically accessible to other nodes, it just marks an already shared storage as such!",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "skip-cert-verification": {
        "default": "false",
        "description": "Disable TLS certificate verification, only enable on fully trusted networks!",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "smbversion": {
        "default": "default",
        "description": "SMB protocol version. 'default' if not set, negotiates the highest SMB2+ version supported by both the client and server.",
        "enum": [
          "default",
          "2.0",
          "2.1",
          "3",
          "3.0",
          "3.11"
        ],
        "optional": 1,
        "type": "string"
      },
      "snapshot-as-volume-chain": {
        "default": 0,
        "description": "Enable support for creating storage-vendor agnostic snapshot through volume backing-chains.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "sparse": {
        "description": "use sparse volumes",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "subdir": {
        "description": "Subdir to mount.",
        "format": "pve-storage-path",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "tagged_only": {
        "description": "Only list logical volumes tagged with 'pve-vm-ID'.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "username": {
        "description": "RBD Id.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "zfs-base-path": {
        "description": "Base path where to look for the created ZFS block devices. Set automatically during creation if not specified. Usually '/dev/zvol'.",
        "format": "pve-storage-path",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/storage",
      [
        "Datastore.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "properties": {
      "config": {
        "additionalProperties": 1,
        "description": "Partial, possibly server generated, configuration properties.",
        "optional": 1,
        "properties": {
          "encryption-key": {
            "description": "The, possibly auto-generated, encryption-key.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "storage": {
        "description": "The ID of the created storage.",
        "type": "string"
      },
      "type": {
        "description": "The type of the created storage.",
        "enum": [
          "btrfs",
          "cephfs",
          "cifs",
          "dir",
          "esxi",
          "iscsi",
          "iscsidirect",
          "lvm",
          "lvmthin",
          "nfs",
          "pbs",
          "rbd",
          "zfs",
          "zfspool"
        ],
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
