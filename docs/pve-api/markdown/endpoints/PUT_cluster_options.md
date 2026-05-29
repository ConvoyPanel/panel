# PUT /cluster/options

Set datacenter options.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| bwlimit | string | no | Set I/O bandwidth limit for various operations (in KiB/s). |
| consent-text | string | no | Consent text that is displayed before logging in. |
| console | string | no | Select the default Console viewer. You can either use the builtin java applet (VNC; deprecated and maps to html5), an external virt-viewer comtatible application (SPICE), an HTML5 based vnc viewer (noVNC), or an HTML5 based console client (xtermjs). If the selected viewer is not available (e.g. SPICE not activated for the VM), the fallback is noVNC. |
| crs | string | no | Cluster resource scheduling settings. |
| delete | string | no | A list of settings you want to delete. |
| description | string | no | Datacenter description. Shown in the web-interface datacenter notes panel. This is saved as comment inside the configuration file. |
| email_from | string | no | Specify email address to send notification from (default is root@$hostname) |
| fencing | string | no | Set the fencing mode of the HA cluster. Hardware mode needs a valid configuration of fence devices in /etc/pve/ha/fence.cfg. With both all two modes are used.  WARNING: 'hardware' and 'both' are EXPERIMENTAL & WIP |
| ha | string | no | Cluster wide HA settings. |
| http_proxy | string | no | Specify external http proxy which is used for downloads (example: 'http://username:password@host:port/') |
| keyboard | string | no | Default keybord layout for vnc server. |
| language | string | no | Default GUI language. |
| location | string | no | The location of the cluster. |
| mac_prefix | string | no | Prefix for the auto-generated MAC addresses of virtual guests. The default 'BC:24:11' is the OUI assigned by the IEEE to Proxmox Server Solutions GmbH for a 24-bit large MAC block. You're allowed to use this in local networks, i.e., those not directly reachable by the public (e.g., in a LAN or behind NAT). |
| max_workers | integer | no | Defines how many workers (per node) are maximal started  on actions like 'stopall VMs' or task from the ha-manager. |
| migration | string | no | For cluster wide migration settings. |
| migration_unsecure | boolean | no | Migration is secure using SSH tunnel by default. For secure private networks you can disable it to speed up migration. Deprecated, use the 'migration' property instead! |
| next-id | string | no | Control the range for the free VMID auto-selection pool. |
| notify | string | no | Cluster-wide notification settings. |
| registered-tags | string | no | A list of tags that require a `Sys.Modify` on '/' to set and delete. Tags set here that are also in 'user-tag-access' also require `Sys.Modify`. |
| replication | string | no | For cluster wide replication settings. |
| tag-style | string | no | Tag style options. |
| u2f | string | no | u2f |
| user-tag-access | string | no | Privilege options for user-settable tags |
| webauthn | string | no | webauthn configuration |

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
  "description": "Set datacenter options.",
  "method": "PUT",
  "name": "set_options",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
      "consent-text": {
        "description": "Consent text that is displayed before logging in.",
        "maxLength": 65536,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "console": {
        "description": "Select the default Console viewer. You can either use the builtin java applet (VNC; deprecated and maps to html5), an external virt-viewer comtatible application (SPICE), an HTML5 based vnc viewer (noVNC), or an HTML5 based console client (xtermjs). If the selected viewer is not available (e.g. SPICE not activated for the VM), the fallback is noVNC.",
        "enum": [
          "applet",
          "vv",
          "html5",
          "xtermjs"
        ],
        "optional": 1,
        "type": "string"
      },
      "crs": {
        "description": "Cluster resource scheduling settings.",
        "format": {
          "ha": {
            "default": "basic",
            "description": "Use this resource scheduler mode for HA.",
            "enum": [
              "basic",
              "static",
              "dynamic"
            ],
            "optional": 1,
            "type": "string",
            "verbose_description": "Configures how the HA Manager should select nodes to start or recover services:\n\n- with 'basic', only the number of services is used,\n- with 'static', static CPU and memory configuration of services are considered,\n- with 'dynamic', static and dynamic CPU and memory usage of services are considered.\n"
          },
          "ha-auto-rebalance": {
            "default": 0,
            "description": "Whether to use CRS for balancing HA resources automatically depending on the current node imbalance.",
            "optional": 1,
            "type": "boolean"
          },
          "ha-auto-rebalance-hold-duration": {
            "default": 3,
            "description": "The number of HA rounds for which the cluster node imbalance threshold must be exceeded before triggering an automatic resource balancing migration.",
            "minimum": 0,
            "optional": 1,
            "requires": "ha-auto-rebalance",
            "type": "number"
          },
          "ha-auto-rebalance-margin": {
            "default": 10,
            "description": "The minimum relative improvement in cluster node imbalance, in percent, to commit to a resource balancing migration.",
            "maximum": 100,
            "minimum": 0,
            "optional": 1,
            "requires": "ha-auto-rebalance",
            "type": "number"
          },
          "ha-auto-rebalance-method": {
            "default": "bruteforce",
            "description": "The method to use for the scoring of balancing migrations.",
            "enum": [
              "bruteforce",
              "topsis"
            ],
            "optional": 1,
            "requires": "ha-auto-rebalance",
            "type": "string"
          },
          "ha-auto-rebalance-threshold": {
            "default": 30,
            "description": "The cluster node imbalance, in percent, which will trigger the automatic resource balancing system if exceeded.",
            "maximum": 100,
            "minimum": 0,
            "optional": 1,
            "requires": "ha-auto-rebalance",
            "type": "number"
          },
          "ha-rebalance-on-start": {
            "default": 0,
            "description": "Set to use CRS for selecting a suited node when a HA services request-state changes from stop to start.",
            "optional": 1,
            "type": "boolean"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[ha=<basic|static|dynamic>] [,ha-auto-rebalance=<1|0>] [,ha-auto-rebalance-hold-duration=<number>] [,ha-auto-rebalance-margin=<number>] [,ha-auto-rebalance-method=<bruteforce|topsis>] [,ha-auto-rebalance-threshold=<number>] [,ha-rebalance-on-start=<1|0>]"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "description": {
        "description": "Datacenter description. Shown in the web-interface datacenter notes panel. This is saved as comment inside the configuration file.",
        "maxLength": 65536,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "email_from": {
        "description": "Specify email address to send notification from (default is root@$hostname)",
        "format": "email-opt",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "fencing": {
        "default": "watchdog",
        "description": "Set the fencing mode of the HA cluster. Hardware mode needs a valid configuration of fence devices in /etc/pve/ha/fence.cfg. With both all two modes are used.\n\nWARNING: 'hardware' and 'both' are EXPERIMENTAL & WIP",
        "enum": [
          "watchdog",
          "hardware",
          "both"
        ],
        "optional": 1,
        "type": "string"
      },
      "ha": {
        "description": "Cluster wide HA settings.",
        "format": {
          "shutdown_policy": {
            "default": "conditional",
            "description": "The policy for HA services on node shutdown. 'freeze' disables auto-recovery, 'failover' ensures recovery, 'conditional' recovers on poweroff and freezes on reboot. 'migrate' will migrate running services to other nodes, if possible. With 'freeze' or 'failover', HA Services will always get stopped first on shutdown.",
            "enum": [
              "freeze",
              "failover",
              "conditional",
              "migrate"
            ],
            "type": "string",
            "verbose_description": "Describes the policy for handling HA services on poweroff or reboot of a node. Freeze will always freeze services which are still located on the node on shutdown, those services won't be recovered by the HA manager. Failover will not mark the services as frozen and thus the services will get recovered to other nodes, if the shutdown node does not come up again quickly (< 1min). 'conditional' chooses automatically depending on the type of shutdown, i.e., on a reboot the service will be frozen but on a poweroff the service will stay as is, and thus get recovered after about 2 minutes. Migrate will try to move all running services to another node when a reboot or shutdown was triggered. The poweroff process will only continue once no running services are located on the node anymore. If the node comes up again, the service will be moved back to the previously powered-off node, at least if no other migration, reloaction or recovery took place."
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "shutdown_policy=<enum>"
      },
      "http_proxy": {
        "description": "Specify external http proxy which is used for downloads (example: 'http://username:password@host:port/')",
        "optional": 1,
        "pattern": "http://.*",
        "type": "string"
      },
      "keyboard": {
        "description": "Default keybord layout for vnc server.",
        "enum": [
          "de",
          "de-ch",
          "da",
          "en-gb",
          "en-us",
          "es",
          "fi",
          "fr",
          "fr-be",
          "fr-ca",
          "fr-ch",
          "hu",
          "is",
          "it",
          "ja",
          "lt",
          "mk",
          "nl",
          "no",
          "pl",
          "pt",
          "pt-br",
          "sv",
          "sl",
          "tr"
        ],
        "optional": 1,
        "type": "string"
      },
      "language": {
        "description": "Default GUI language.",
        "enum": [
          "ar",
          "ca",
          "da",
          "de",
          "en",
          "es",
          "eu",
          "fa",
          "fr",
          "hr",
          "he",
          "it",
          "ja",
          "ka",
          "kr",
          "nb",
          "nl",
          "nn",
          "pl",
          "pt_BR",
          "ru",
          "sl",
          "sv",
          "tr",
          "ukr",
          "zh_CN",
          "zh_TW"
        ],
        "optional": 1,
        "type": "string"
      },
      "location": {
        "description": "The location of the cluster.",
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
      "mac_prefix": {
        "default": "BC:24:11",
        "description": "Prefix for the auto-generated MAC addresses of virtual guests. The default 'BC:24:11' is the OUI assigned by the IEEE to Proxmox Server Solutions GmbH for a 24-bit large MAC block. You're allowed to use this in local networks, i.e., those not directly reachable by the public (e.g., in a LAN or behind NAT).",
        "format": "mac-prefix",
        "optional": 1,
        "type": "string",
        "typetext": "<string>",
        "verbose_description": "Prefix for the auto-generated MAC addresses of virtual guests. The default `BC:24:11` is the Organizationally Unique Identifier (OUI) assigned by the IEEE to Proxmox Server Solutions GmbH for a MAC Address Block Large (MA-L). You're allowed to use this in local networks, i.e., those not directly reachable by the public (e.g., in a LAN or NAT/Masquerading).\n \nNote that when you run multiple cluster that (partially) share the networks of their virtual guests, it's highly recommended that you extend the default MAC prefix, or generate a custom (valid) one, to reduce the chance of MAC collisions. For example, add a separate extra hexadecimal to the Proxmox OUI for each cluster, like `BC:24:11:0` for the first, `BC:24:11:1` for the second, and so on.\n Alternatively, you can also separate the networks of the guests logically, e.g., by using VLANs.\n\nFor publicly accessible guests it's recommended that you get your own https://standards.ieee.org/products-programs/regauth/[OUI from the IEEE] registered or coordinate with your, or your hosting providers, network admins."
      },
      "max_workers": {
        "description": "Defines how many workers (per node) are maximal started  on actions like 'stopall VMs' or task from the ha-manager.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      },
      "migration": {
        "description": "For cluster wide migration settings.",
        "format": {
          "network": {
            "description": "CIDR of the (sub) network that is used for migration. Used as a fallback for replications jobs if the replication network setting is not set",
            "format": "CIDR",
            "format_description": "CIDR",
            "optional": 1,
            "type": "string"
          },
          "type": {
            "default": "secure",
            "default_key": 1,
            "description": "Migration traffic is encrypted using an SSH tunnel by default. On secure, completely private networks this can be disabled to increase performance.",
            "enum": [
              "secure",
              "insecure"
            ],
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[type=]<secure|insecure> [,network=<CIDR>]"
      },
      "migration_unsecure": {
        "description": "Migration is secure using SSH tunnel by default. For secure private networks you can disable it to speed up migration. Deprecated, use the 'migration' property instead!",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "next-id": {
        "description": "Control the range for the free VMID auto-selection pool.",
        "format": {
          "lower": {
            "default": 100,
            "description": "Lower, inclusive boundary for free next-id API range.",
            "max": 999999999,
            "min": 100,
            "optional": 1,
            "type": "integer"
          },
          "upper": {
            "default": 1000000,
            "description": "Upper, exclusive boundary for free next-id API range.",
            "max": 1000000000,
            "min": 100,
            "optional": 1,
            "type": "integer"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[lower=<integer>] [,upper=<integer>]"
      },
      "notify": {
        "description": "Cluster-wide notification settings.",
        "format": {
          "fencing": {
            "description": "UNUSED - Use datacenter notification settings instead.",
            "enum": [
              "always",
              "never"
            ],
            "optional": 1,
            "type": "string"
          },
          "package-updates": {
            "default": "auto",
            "description": "DEPRECATED: Use datacenter notification settings instead. Control when the daily update job should send out notifications.",
            "enum": [
              "auto",
              "always",
              "never"
            ],
            "optional": 1,
            "type": "string",
            "verbose_description": "DEPRECATED: Use datacenter notification settings instead.\nControl how often the daily update job should send out notifications:\n* 'auto' daily for systems with a valid subscription, as those are assumed to be  production-ready and thus should know about pending updates.\n* 'always' every update, if there are new pending updates.\n* 'never' never send a notification for new pending updates.\n"
          },
          "replication": {
            "description": "UNUSED - Use datacenter notification settings instead.",
            "enum": [
              "always",
              "never"
            ],
            "optional": 1,
            "type": "string"
          },
          "target-fencing": {
            "description": "UNUSED - Use datacenter notification settings instead.",
            "format_description": "TARGET",
            "optional": 1,
            "type": "string"
          },
          "target-package-updates": {
            "description": "UNUSED - Use datacenter notification settings instead.",
            "format_description": "TARGET",
            "optional": 1,
            "type": "string"
          },
          "target-replication": {
            "description": "UNUSED - Use datacenter notification settings instead.",
            "format_description": "TARGET",
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[fencing=<always|never>] [,package-updates=<auto|always|never>] [,replication=<always|never>] [,target-fencing=<TARGET>] [,target-package-updates=<TARGET>] [,target-replication=<TARGET>]"
      },
      "registered-tags": {
        "description": "A list of tags that require a `Sys.Modify` on '/' to set and delete. Tags set here that are also in 'user-tag-access' also require `Sys.Modify`.",
        "optional": 1,
        "pattern": "(?:(?^i:[a-z0-9_][a-z0-9_\\-\\+\\.]*);)*(?^i:[a-z0-9_][a-z0-9_\\-\\+\\.]*)",
        "type": "string",
        "typetext": "<tag>[;<tag>...]"
      },
      "replication": {
        "description": "For cluster wide replication settings.",
        "format": {
          "network": {
            "description": "CIDR of the (sub) network that is used for replication jobs.",
            "format": "CIDR",
            "format_description": "CIDR",
            "optional": 1,
            "type": "string"
          },
          "type": {
            "default": "secure",
            "default_key": 1,
            "description": "Replication traffic is encrypted using an SSH tunnel by default. On secure, completely private networks this can be disabled to increase performance.",
            "enum": [
              "secure",
              "insecure"
            ],
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[type=]<secure|insecure> [,network=<CIDR>]"
      },
      "tag-style": {
        "description": "Tag style options.",
        "format": {
          "case-sensitive": {
            "default": 0,
            "description": "Controls if filtering for unique tags on update should check case-sensitive.",
            "optional": 1,
            "type": "boolean"
          },
          "color-map": {
            "description": "Manual color mapping for tags (semicolon separated).",
            "optional": 1,
            "pattern": "(?:(?^i:[a-z0-9_][a-z0-9_\\-\\+\\.]*):[0-9a-fA-F]{6}(?::[0-9a-fA-F]{6})?)(?:;(?:(?^i:[a-z0-9_][a-z0-9_\\-\\+\\.]*):[0-9a-fA-F]{6}(?::[0-9a-fA-F]{6})?))*",
            "type": "string",
            "typetext": "<tag>:<hex-color>[:<hex-color-for-text>][;<tag>=...]"
          },
          "ordering": {
            "default": "alphabetical",
            "description": "Controls the sorting of the tags in the web-interface and the API update.",
            "enum": [
              "config",
              "alphabetical"
            ],
            "optional": 1,
            "type": "string"
          },
          "shape": {
            "default": "circle",
            "description": "Tag shape for the web ui tree. 'full' draws the full tag. 'circle' draws only a circle with the background color. 'dense' only draws a small rectancle (useful when many tags are assigned to each guest).'none' disables showing the tags.",
            "enum": [
              "full",
              "circle",
              "dense",
              "none"
            ],
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[case-sensitive=<1|0>] [,color-map=<tag>:<hex-color>[:<hex-color-for-text>][;<tag>=...]] [,ordering=<config|alphabetical>] [,shape=<enum>]"
      },
      "u2f": {
        "description": "u2f",
        "format": {
          "appid": {
            "description": "U2F AppId URL override. Defaults to the origin.",
            "format_description": "APPID",
            "optional": 1,
            "type": "string"
          },
          "origin": {
            "description": "U2F Origin override. Mostly useful for single nodes with a single URL.",
            "format_description": "URL",
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[appid=<APPID>] [,origin=<URL>]"
      },
      "user-tag-access": {
        "description": "Privilege options for user-settable tags",
        "format": {
          "user-allow": {
            "default": "free",
            "description": "Controls tag usage for users without `Sys.Modify` on `/` by either allowing `none`, a `list`, already `existing` or anything (`free`).",
            "enum": [
              "none",
              "list",
              "existing",
              "free"
            ],
            "optional": 1,
            "type": "string",
            "verbose_description": "Controls which tags can be set or deleted on resources a user controls (such as guests). Users with the `Sys.Modify` privilege on `/` are alwaysunrestricted.\n* 'none' no tags are usable.\n* 'list' tags from 'user-allow-list' are usable.\n* 'existing' like list, but already existing tags of resources are also usable.\n* 'free' no tag restrictions.\n"
          },
          "user-allow-list": {
            "description": "List of tags users are allowed to set and delete (semicolon separated) for 'user-allow' values 'list' and 'existing'.",
            "optional": 1,
            "pattern": "(?^i:[a-z0-9_][a-z0-9_\\-\\+\\.]*)(?:;(?^i:[a-z0-9_][a-z0-9_\\-\\+\\.]*))*",
            "type": "string",
            "typetext": "<tag>[;<tag>...]"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[user-allow=<enum>] [,user-allow-list=<tag>[;<tag>...]]"
      },
      "webauthn": {
        "description": "webauthn configuration",
        "format": {
          "allow-subdomains": {
            "default": 1,
            "description": "Whether to allow the origin to be a subdomain, rather than the exact URL.",
            "optional": 1,
            "type": "boolean"
          },
          "id": {
            "description": "Relying party ID. Must be the domain name without protocol, port or location. Changing this *will* break existing credentials.",
            "format_description": "DOMAINNAME",
            "optional": 1,
            "type": "string"
          },
          "origin": {
            "description": "Site origin. Must be a `https://` URL (or `http://localhost`). Should contain the address users type in their browsers to access the web interface. Changing this *may* break existing credentials.",
            "format_description": "URL",
            "optional": 1,
            "type": "string"
          },
          "rp": {
            "description": "Relying party name. Any text identifier. Changing this *may* break existing credentials.",
            "format_description": "RELYING_PARTY",
            "optional": 1,
            "type": "string"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "[allow-subdomains=<1|0>] [,id=<DOMAINNAME>] [,origin=<URL>] [,rp=<RELYING_PARTY>]"
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
  "returns": {
    "type": "null"
  }
}
```
