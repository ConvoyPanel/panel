# PUT /nodes/{node}/config

Set node configuration options.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| acme | string | no | Node specific ACME settings. |
| acmedomain[n] | string | no | ACME domain and validation plugin |
| ballooning-target | integer | no | RAM usage target for ballooning (in percent of total memory) |
| delete | string | no | A list of settings you want to delete. |
| description | string | no | Description for the Node. Shown in the web-interface node notes panel. This is saved as comment inside the configuration file. |
| digest | string | no | Prevent changes if current configuration file has different SHA1 digest. This can be used to prevent concurrent modifications. |
| location | string | no | The location of the node. Overrides the default from the datacenter config. |
| startall-onboot-delay | integer | no | Initial delay in seconds, before starting all the Virtual Guests with on-boot enabled. |
| wakeonlan | string | no | Node specific wake on LAN settings. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Set node configuration options.",
  "method": "PUT",
  "name": "set_options",
  "parameters": {
    "additionalProperties": 0,
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
        "type": "string",
        "typetext": "[account=<name>] [,domains=<domain[;domain;...]>]"
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
        "type": "string",
        "typetext": "[domain=]<domain> [,alias=<domain>] [,plugin=<name of the plugin configuration>]"
      },
      "ballooning-target": {
        "default": 80,
        "description": "RAM usage target for ballooning (in percent of total memory)",
        "maximum": 100,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 100)"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "description": {
        "description": "Description for the Node. Shown in the web-interface node notes panel. This is saved as comment inside the configuration file.",
        "maxLength": 65536,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has different SHA1 digest. This can be used to prevent concurrent modifications.",
        "maxLength": 40,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
        "type": "string",
        "typetext": "latitude=<number> ,longitude=<number> [,name=<name>]"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "startall-onboot-delay": {
        "default": 0,
        "description": "Initial delay in seconds, before starting all the Virtual Guests with on-boot enabled.",
        "maximum": 300,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 300)"
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
        "type": "string",
        "typetext": "[mac=]<MAC address> [,bind-interface=<bind interface>] [,broadcast-address=<IPv4 broadcast address>]"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
