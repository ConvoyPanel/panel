# GET /nodes/{node}/config

Get node configuration options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| property | string | no | Return only a specific property from the node configuration. |

## Returns

```json
{
  "properties": {
    "acme": {
      "description": "Node specific ACME settings.",
      "format": {
        "account": {
          "default": "default",
          "description": "ACME account config file name.",
          "format": "pve-configid",
          "format_description": "name",
          "optional": 1,
          "type": "string"
        },
        "domains": {
          "description": "List of domains for this node's ACME certificate",
          "format": "pve-acme-domain-list",
          "format_description": "domain[;domain;...]",
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "acmedomain[n]": {
      "description": "ACME domain and validation plugin",
      "format": {
        "alias": {
          "description": "Alias for the Domain to verify ACME Challenge over DNS",
          "format": "pve-acme-alias",
          "format_description": "domain",
          "optional": 1,
          "type": "string"
        },
        "domain": {
          "default_key": 1,
          "description": "domain for this node's ACME certificate",
          "format": "pve-acme-domain",
          "format_description": "domain",
          "type": "string"
        },
        "plugin": {
          "default": "standalone",
          "description": "The ACME plugin ID",
          "format": "pve-configid",
          "format_description": "name of the plugin configuration",
          "optional": 1,
          "type": "string"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "ballooning-target": {
      "default": 80,
      "description": "RAM usage target for ballooning (in percent of total memory)",
      "maximum": 100,
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "description": {
      "description": "Description for the Node. Shown in the web-interface node notes panel. This is saved as comment inside the configuration file.",
      "maxLength": 65536,
      "optional": 1,
      "type": "string"
    },
    "digest": {
      "description": "Prevent changes if current configuration file has different SHA1 digest. This can be used to prevent concurrent modifications.",
      "maxLength": 40,
      "optional": 1,
      "type": "string"
    },
    "location": {
      "description": "The location of the node. Overrides the default from the datacenter config.",
      "format": {
        "latitude": {
          "description": "The latitude of the nodes location in degrees.",
          "maximum": 90,
          "minimum": -90,
          "type": "number"
        },
        "longitude": {
          "description": "The longitude of the nodes location in degrees.",
          "maximum": 180,
          "minimum": -180,
          "type": "number"
        },
        "name": {
          "description": "The name of the location of this node",
          "maxLength": 128,
          "optional": 1,
          "type": "string",
          "typetext": "<name>"
        }
      },
      "optional": 1,
      "type": "string"
    },
    "startall-onboot-delay": {
      "default": 0,
      "description": "Initial delay in seconds, before starting all the Virtual Guests with on-boot enabled.",
      "maximum": 300,
      "minimum": 0,
      "optional": 1,
      "type": "integer"
    },
    "wakeonlan": {
      "description": "Node specific wake on LAN settings.",
      "format": {
        "bind-interface": {
          "default": "The interface carrying the default route",
          "description": "Bind to this interface when sending wake on LAN packet",
          "format": "pve-iface",
          "format_description": "bind interface",
          "optional": 1,
          "type": "string"
        },
        "broadcast-address": {
          "default": "255.255.255.255",
          "description": "IPv4 broadcast address to use when sending wake on LAN packet",
          "format": "ipv4",
          "format_description": "IPv4 broadcast address",
          "optional": 1,
          "type": "string"
        },
        "mac": {
          "default_key": 1,
          "description": "MAC address for wake on LAN",
          "format": "mac-addr",
          "format_description": "MAC address",
          "type": "string"
        }
      },
      "optional": 1,
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
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get node configuration options.",
  "method": "GET",
  "name": "get_config",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "property": {
        "default": "all",
        "description": "Return only a specific property from the node configuration.",
        "enum": [
          "acme",
          "acmedomain0",
          "acmedomain1",
          "acmedomain2",
          "acmedomain3",
          "acmedomain4",
          "acmedomain5",
          "ballooning-target",
          "description",
          "location",
          "startall-onboot-delay",
          "wakeonlan"
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
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "properties": {
      "acme": {
        "description": "Node specific ACME settings.",
        "format": {
          "account": {
            "default": "default",
            "description": "ACME account config file name.",
            "format": "pve-configid",
            "format_description": "name",
            "optional": 1,
            "type": "string"
          },
          "domains": {
            "description": "List of domains for this node's ACME certificate",
            "format": "pve-acme-domain-list",
            "format_description": "domain[;domain;...]",
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "acmedomain[n]": {
        "description": "ACME domain and validation plugin",
        "format": {
          "alias": {
            "description": "Alias for the Domain to verify ACME Challenge over DNS",
            "format": "pve-acme-alias",
            "format_description": "domain",
            "optional": 1,
            "type": "string"
          },
          "domain": {
            "default_key": 1,
            "description": "domain for this node's ACME certificate",
            "format": "pve-acme-domain",
            "format_description": "domain",
            "type": "string"
          },
          "plugin": {
            "default": "standalone",
            "description": "The ACME plugin ID",
            "format": "pve-configid",
            "format_description": "name of the plugin configuration",
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "ballooning-target": {
        "default": 80,
        "description": "RAM usage target for ballooning (in percent of total memory)",
        "maximum": 100,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "description": {
        "description": "Description for the Node. Shown in the web-interface node notes panel. This is saved as comment inside the configuration file.",
        "maxLength": 65536,
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has different SHA1 digest. This can be used to prevent concurrent modifications.",
        "maxLength": 40,
        "optional": 1,
        "type": "string"
      },
      "location": {
        "description": "The location of the node. Overrides the default from the datacenter config.",
        "format": {
          "latitude": {
            "description": "The latitude of the nodes location in degrees.",
            "maximum": 90,
            "minimum": -90,
            "type": "number"
          },
          "longitude": {
            "description": "The longitude of the nodes location in degrees.",
            "maximum": 180,
            "minimum": -180,
            "type": "number"
          },
          "name": {
            "description": "The name of the location of this node",
            "maxLength": 128,
            "optional": 1,
            "type": "string",
            "typetext": "<name>"
          }
        },
        "optional": 1,
        "type": "string"
      },
      "startall-onboot-delay": {
        "default": 0,
        "description": "Initial delay in seconds, before starting all the Virtual Guests with on-boot enabled.",
        "maximum": 300,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      },
      "wakeonlan": {
        "description": "Node specific wake on LAN settings.",
        "format": {
          "bind-interface": {
            "default": "The interface carrying the default route",
            "description": "Bind to this interface when sending wake on LAN packet",
            "format": "pve-iface",
            "format_description": "bind interface",
            "optional": 1,
            "type": "string"
          },
          "broadcast-address": {
            "default": "255.255.255.255",
            "description": "IPv4 broadcast address to use when sending wake on LAN packet",
            "format": "ipv4",
            "format_description": "IPv4 broadcast address",
            "optional": 1,
            "type": "string"
          },
          "mac": {
            "default_key": 1,
            "description": "MAC address for wake on LAN",
            "format": "mac-addr",
            "format_description": "MAC address",
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
