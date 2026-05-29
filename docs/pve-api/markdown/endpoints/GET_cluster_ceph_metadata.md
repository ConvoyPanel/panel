# GET /cluster/ceph/metadata

Get ceph metadata.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| scope | string | no | Which metadata facet to return: 'all' enriches the per-daemon metadata with the PVE-side service state (presence of unit, data directory), 'versions' collects only per-node Ceph binary version data. |

## Returns

```json
{
  "description": "Items for each type of service containing objects for each instance.",
  "properties": {
    "mds": {
      "additionalProperties": {
        "additionalProperties": 1,
        "description": "Useful properties are listed, but not the full list.",
        "properties": {
          "addr": {
            "description": "Bind addresses and ports.",
            "optional": 1,
            "type": "string"
          },
          "ceph_release": {
            "description": "Ceph release codename currently used.",
            "type": "string"
          },
          "ceph_version": {
            "description": "Version info currently used by the service.",
            "type": "string"
          },
          "ceph_version_short": {
            "description": "Short version (numerical) info currently used by the service.",
            "type": "string"
          },
          "hostname": {
            "description": "Hostname on which the service is running.",
            "type": "string"
          },
          "mem_swap_kb": {
            "description": "Memory of the service currently in swap.",
            "type": "integer"
          },
          "mem_total_kb": {
            "description": "Memory consumption of the service.",
            "type": "integer"
          },
          "name": {
            "description": "Name of the service instance.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "description": "Metadata servers configured in the cluster and their properties, keyed by '<name>@<host>'.",
      "type": "object"
    },
    "mgr": {
      "additionalProperties": {
        "additionalProperties": 1,
        "description": "Useful properties are listed, but not the full list.",
        "properties": {
          "addr": {
            "description": "Bind address.",
            "optional": 1,
            "type": "string"
          },
          "ceph_release": {
            "description": "Ceph release codename currently used.",
            "type": "string"
          },
          "ceph_version": {
            "description": "Version info currently used by the service.",
            "type": "string"
          },
          "ceph_version_short": {
            "description": "Short version (numerical) info currently used by the service.",
            "type": "string"
          },
          "hostname": {
            "description": "Hostname on which the service is running.",
            "type": "string"
          },
          "mem_swap_kb": {
            "description": "Memory of the service currently in swap.",
            "type": "integer"
          },
          "mem_total_kb": {
            "description": "Memory consumption of the service.",
            "type": "integer"
          },
          "name": {
            "description": "Name of the service instance.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "description": "Managers configured in the cluster and their properties, keyed by '<name>@<host>'.",
      "type": "object"
    },
    "mon": {
      "additionalProperties": {
        "additionalProperties": 1,
        "description": "Useful properties are listed, but not the full list.",
        "properties": {
          "addrs": {
            "description": "Bind addresses and ports.",
            "optional": 1,
            "type": "string"
          },
          "ceph_release": {
            "description": "Ceph release codename currently used.",
            "type": "string"
          },
          "ceph_version": {
            "description": "Version info currently used by the service.",
            "type": "string"
          },
          "ceph_version_short": {
            "description": "Short version (numerical) info currently used by the service.",
            "type": "string"
          },
          "hostname": {
            "description": "Hostname on which the service is running.",
            "type": "string"
          },
          "mem_swap_kb": {
            "description": "Memory of the service currently in swap.",
            "type": "integer"
          },
          "mem_total_kb": {
            "description": "Memory consumption of the service.",
            "type": "integer"
          },
          "name": {
            "description": "Name of the service instance.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "description": "Monitors configured in the cluster and their properties, keyed by '<name>@<host>'.",
      "type": "object"
    },
    "node": {
      "additionalProperties": {
        "additionalProperties": 1,
        "properties": {
          "buildcommit": {
            "description": "GIT commit used for the build.",
            "type": "string"
          },
          "version": {
            "description": "Version info.",
            "properties": {
              "parts": {
                "description": "Major, minor and patch version numbers.",
                "items": {
                  "description": "Version-component string.",
                  "type": "string"
                },
                "type": "array"
              },
              "str": {
                "description": "Version as single string.",
                "type": "string"
              }
            },
            "type": "object"
          }
        },
        "type": "object"
      },
      "description": "Ceph version installed on the nodes, keyed by node name.",
      "type": "object"
    },
    "osd": {
      "description": "OSDs configured in the cluster and their properties.",
      "items": {
        "description": "Useful properties are listed, but not the full list.",
        "properties": {
          "back_addr": {
            "description": "Bind addresses and ports for backend inter OSD traffic.",
            "type": "string"
          },
          "ceph_release": {
            "description": "Ceph release codename currently used.",
            "type": "string"
          },
          "ceph_version": {
            "description": "Version info currently used by the service.",
            "type": "string"
          },
          "ceph_version_short": {
            "description": "Short version (numerical) info currently used by the service.",
            "type": "string"
          },
          "device_ids": {
            "description": "Comma-joined list of device identifiers (e.g. 'sdb=<serial>,sdc=<serial>').",
            "optional": 1,
            "type": "string"
          },
          "device_paths": {
            "description": "Comma-joined list of /dev/disk/by-path entries for the underlying devices.",
            "optional": 1,
            "type": "string"
          },
          "devices": {
            "description": "Comma-joined list of underlying device names (e.g. 'sdb,sdc').",
            "optional": 1,
            "type": "string"
          },
          "front_addr": {
            "description": "Bind addresses and ports for frontend traffic to OSDs.",
            "type": "string"
          },
          "hostname": {
            "description": "Hostname on which the service is running.",
            "type": "string"
          },
          "id": {
            "description": "OSD ID.",
            "type": "integer"
          },
          "mem_swap_kb": {
            "description": "Memory of the service currently in swap.",
            "type": "integer"
          },
          "mem_total_kb": {
            "description": "Memory consumption of the service.",
            "type": "integer"
          },
          "osd_data": {
            "description": "Path to the OSD data directory.",
            "type": "string"
          },
          "osd_objectstore": {
            "description": "OSD objectstore type.",
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
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
    "/",
    [
      "Sys.Audit",
      "Datastore.Audit"
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
  "description": "Get ceph metadata.",
  "method": "GET",
  "name": "metadata",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "scope": {
        "default": "all",
        "description": "Which metadata facet to return: 'all' enriches the per-daemon metadata with the PVE-side service state (presence of unit, data directory), 'versions' collects only per-node Ceph binary version data.",
        "enum": [
          "all",
          "versions"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit",
        "Datastore.Audit"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "returns": {
    "description": "Items for each type of service containing objects for each instance.",
    "properties": {
      "mds": {
        "additionalProperties": {
          "additionalProperties": 1,
          "description": "Useful properties are listed, but not the full list.",
          "properties": {
            "addr": {
              "description": "Bind addresses and ports.",
              "optional": 1,
              "type": "string"
            },
            "ceph_release": {
              "description": "Ceph release codename currently used.",
              "type": "string"
            },
            "ceph_version": {
              "description": "Version info currently used by the service.",
              "type": "string"
            },
            "ceph_version_short": {
              "description": "Short version (numerical) info currently used by the service.",
              "type": "string"
            },
            "hostname": {
              "description": "Hostname on which the service is running.",
              "type": "string"
            },
            "mem_swap_kb": {
              "description": "Memory of the service currently in swap.",
              "type": "integer"
            },
            "mem_total_kb": {
              "description": "Memory consumption of the service.",
              "type": "integer"
            },
            "name": {
              "description": "Name of the service instance.",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "description": "Metadata servers configured in the cluster and their properties, keyed by '<name>@<host>'.",
        "type": "object"
      },
      "mgr": {
        "additionalProperties": {
          "additionalProperties": 1,
          "description": "Useful properties are listed, but not the full list.",
          "properties": {
            "addr": {
              "description": "Bind address.",
              "optional": 1,
              "type": "string"
            },
            "ceph_release": {
              "description": "Ceph release codename currently used.",
              "type": "string"
            },
            "ceph_version": {
              "description": "Version info currently used by the service.",
              "type": "string"
            },
            "ceph_version_short": {
              "description": "Short version (numerical) info currently used by the service.",
              "type": "string"
            },
            "hostname": {
              "description": "Hostname on which the service is running.",
              "type": "string"
            },
            "mem_swap_kb": {
              "description": "Memory of the service currently in swap.",
              "type": "integer"
            },
            "mem_total_kb": {
              "description": "Memory consumption of the service.",
              "type": "integer"
            },
            "name": {
              "description": "Name of the service instance.",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "description": "Managers configured in the cluster and their properties, keyed by '<name>@<host>'.",
        "type": "object"
      },
      "mon": {
        "additionalProperties": {
          "additionalProperties": 1,
          "description": "Useful properties are listed, but not the full list.",
          "properties": {
            "addrs": {
              "description": "Bind addresses and ports.",
              "optional": 1,
              "type": "string"
            },
            "ceph_release": {
              "description": "Ceph release codename currently used.",
              "type": "string"
            },
            "ceph_version": {
              "description": "Version info currently used by the service.",
              "type": "string"
            },
            "ceph_version_short": {
              "description": "Short version (numerical) info currently used by the service.",
              "type": "string"
            },
            "hostname": {
              "description": "Hostname on which the service is running.",
              "type": "string"
            },
            "mem_swap_kb": {
              "description": "Memory of the service currently in swap.",
              "type": "integer"
            },
            "mem_total_kb": {
              "description": "Memory consumption of the service.",
              "type": "integer"
            },
            "name": {
              "description": "Name of the service instance.",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "description": "Monitors configured in the cluster and their properties, keyed by '<name>@<host>'.",
        "type": "object"
      },
      "node": {
        "additionalProperties": {
          "additionalProperties": 1,
          "properties": {
            "buildcommit": {
              "description": "GIT commit used for the build.",
              "type": "string"
            },
            "version": {
              "description": "Version info.",
              "properties": {
                "parts": {
                  "description": "Major, minor and patch version numbers.",
                  "items": {
                    "description": "Version-component string.",
                    "type": "string"
                  },
                  "type": "array"
                },
                "str": {
                  "description": "Version as single string.",
                  "type": "string"
                }
              },
              "type": "object"
            }
          },
          "type": "object"
        },
        "description": "Ceph version installed on the nodes, keyed by node name.",
        "type": "object"
      },
      "osd": {
        "description": "OSDs configured in the cluster and their properties.",
        "items": {
          "description": "Useful properties are listed, but not the full list.",
          "properties": {
            "back_addr": {
              "description": "Bind addresses and ports for backend inter OSD traffic.",
              "type": "string"
            },
            "ceph_release": {
              "description": "Ceph release codename currently used.",
              "type": "string"
            },
            "ceph_version": {
              "description": "Version info currently used by the service.",
              "type": "string"
            },
            "ceph_version_short": {
              "description": "Short version (numerical) info currently used by the service.",
              "type": "string"
            },
            "device_ids": {
              "description": "Comma-joined list of device identifiers (e.g. 'sdb=<serial>,sdc=<serial>').",
              "optional": 1,
              "type": "string"
            },
            "device_paths": {
              "description": "Comma-joined list of /dev/disk/by-path entries for the underlying devices.",
              "optional": 1,
              "type": "string"
            },
            "devices": {
              "description": "Comma-joined list of underlying device names (e.g. 'sdb,sdc').",
              "optional": 1,
              "type": "string"
            },
            "front_addr": {
              "description": "Bind addresses and ports for frontend traffic to OSDs.",
              "type": "string"
            },
            "hostname": {
              "description": "Hostname on which the service is running.",
              "type": "string"
            },
            "id": {
              "description": "OSD ID.",
              "type": "integer"
            },
            "mem_swap_kb": {
              "description": "Memory of the service currently in swap.",
              "type": "integer"
            },
            "mem_total_kb": {
              "description": "Memory consumption of the service.",
              "type": "integer"
            },
            "osd_data": {
              "description": "Path to the OSD data directory.",
              "type": "string"
            },
            "osd_objectstore": {
              "description": "OSD objectstore type.",
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
