# GET /cluster/acme/plugins

ACME plugin index.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list ACME plugins of a specific type |

## Returns

```json
{
  "items": {
    "properties": {
      "api": {
        "description": "API plugin name",
        "enum": [
          "1984hosting",
          "acmedns",
          "acmeproxy",
          "active24",
          "ad",
          "ali",
          "alviy",
          "anx",
          "artfiles",
          "arvan",
          "aurora",
          "autodns",
          "aws",
          "azion",
          "azure",
          "beget",
          "bookmyname",
          "bunny",
          "cf",
          "clouddns",
          "cloudns",
          "cn",
          "conoha",
          "constellix",
          "cpanel",
          "curanet",
          "cyon",
          "da",
          "ddnss",
          "desec",
          "df",
          "dgon",
          "dnsexit",
          "dnshome",
          "dnsimple",
          "dnsservices",
          "doapi",
          "domeneshop",
          "dp",
          "dpi",
          "dreamhost",
          "duckdns",
          "durabledns",
          "dyn",
          "dynu",
          "dynv6",
          "easydns",
          "edgecenter",
          "edgedns",
          "euserv",
          "exoscale",
          "fornex",
          "freedns",
          "freemyip",
          "gandi_livedns",
          "gcloud",
          "gcore",
          "gd",
          "geoscaling",
          "googledomains",
          "he",
          "he_ddns",
          "hetzner",
          "hetznercloud",
          "hexonet",
          "hostingde",
          "huaweicloud",
          "infoblox",
          "infomaniak",
          "internetbs",
          "inwx",
          "ionos",
          "ionos_cloud",
          "ipv64",
          "ispconfig",
          "jd",
          "joker",
          "kappernet",
          "kas",
          "kinghost",
          "knot",
          "la",
          "leaseweb",
          "lexicon",
          "limacity",
          "linode",
          "linode_v4",
          "loopia",
          "lua",
          "maradns",
          "me",
          "miab",
          "mijnhost",
          "misaka",
          "myapi",
          "mydevil",
          "mydnsjp",
          "mythic_beasts",
          "namecheap",
          "namecom",
          "namesilo",
          "nanelo",
          "nederhost",
          "neodigit",
          "netcup",
          "netlify",
          "nic",
          "njalla",
          "nm",
          "nsd",
          "nsone",
          "nsupdate",
          "nw",
          "oci",
          "omglol",
          "one",
          "online",
          "openprovider",
          "openprovider_rest",
          "openstack",
          "opnsense",
          "ovh",
          "pdns",
          "pleskxml",
          "pointhq",
          "porkbun",
          "rackcorp",
          "rackspace",
          "rage4",
          "rcode0",
          "regru",
          "scaleway",
          "schlundtech",
          "selectel",
          "selfhost",
          "servercow",
          "simply",
          "spaceship",
          "technitium",
          "tele3",
          "tencent",
          "timeweb",
          "transip",
          "udr",
          "ultra",
          "unoeuro",
          "variomedia",
          "veesp",
          "vercel",
          "vscale",
          "vultr",
          "websupport",
          "west_cn",
          "world4you",
          "yandex360",
          "yc",
          "zilore",
          "zone",
          "zoneedit",
          "zonomi"
        ],
        "optional": 1,
        "type": "string"
      },
      "data": {
        "description": "DNS plugin data. (base64 encoded)",
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string"
      },
      "disable": {
        "description": "Flag to disable the config.",
        "optional": 1,
        "type": "boolean"
      },
      "nodes": {
        "description": "List of cluster node names.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string"
      },
      "plugin": {
        "description": "Unique identifier for ACME plugin instance.",
        "format": "pve-configid",
        "type": "string"
      },
      "type": {
        "description": "ACME challenge type.",
        "enum": [
          "dns",
          "standalone"
        ],
        "type": "string"
      },
      "validation-delay": {
        "default": 30,
        "description": "Extra delay in seconds to wait before requesting validation. Allows to cope with a long TTL of DNS records.",
        "maximum": 172800,
        "minimum": 0,
        "optional": 1,
        "type": "integer"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{plugin}",
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
  "description": "ACME plugin index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Only list ACME plugins of a specific type",
        "enum": [
          "dns",
          "standalone"
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
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "items": {
      "properties": {
        "api": {
          "description": "API plugin name",
          "enum": [
            "1984hosting",
            "acmedns",
            "acmeproxy",
            "active24",
            "ad",
            "ali",
            "alviy",
            "anx",
            "artfiles",
            "arvan",
            "aurora",
            "autodns",
            "aws",
            "azion",
            "azure",
            "beget",
            "bookmyname",
            "bunny",
            "cf",
            "clouddns",
            "cloudns",
            "cn",
            "conoha",
            "constellix",
            "cpanel",
            "curanet",
            "cyon",
            "da",
            "ddnss",
            "desec",
            "df",
            "dgon",
            "dnsexit",
            "dnshome",
            "dnsimple",
            "dnsservices",
            "doapi",
            "domeneshop",
            "dp",
            "dpi",
            "dreamhost",
            "duckdns",
            "durabledns",
            "dyn",
            "dynu",
            "dynv6",
            "easydns",
            "edgecenter",
            "edgedns",
            "euserv",
            "exoscale",
            "fornex",
            "freedns",
            "freemyip",
            "gandi_livedns",
            "gcloud",
            "gcore",
            "gd",
            "geoscaling",
            "googledomains",
            "he",
            "he_ddns",
            "hetzner",
            "hetznercloud",
            "hexonet",
            "hostingde",
            "huaweicloud",
            "infoblox",
            "infomaniak",
            "internetbs",
            "inwx",
            "ionos",
            "ionos_cloud",
            "ipv64",
            "ispconfig",
            "jd",
            "joker",
            "kappernet",
            "kas",
            "kinghost",
            "knot",
            "la",
            "leaseweb",
            "lexicon",
            "limacity",
            "linode",
            "linode_v4",
            "loopia",
            "lua",
            "maradns",
            "me",
            "miab",
            "mijnhost",
            "misaka",
            "myapi",
            "mydevil",
            "mydnsjp",
            "mythic_beasts",
            "namecheap",
            "namecom",
            "namesilo",
            "nanelo",
            "nederhost",
            "neodigit",
            "netcup",
            "netlify",
            "nic",
            "njalla",
            "nm",
            "nsd",
            "nsone",
            "nsupdate",
            "nw",
            "oci",
            "omglol",
            "one",
            "online",
            "openprovider",
            "openprovider_rest",
            "openstack",
            "opnsense",
            "ovh",
            "pdns",
            "pleskxml",
            "pointhq",
            "porkbun",
            "rackcorp",
            "rackspace",
            "rage4",
            "rcode0",
            "regru",
            "scaleway",
            "schlundtech",
            "selectel",
            "selfhost",
            "servercow",
            "simply",
            "spaceship",
            "technitium",
            "tele3",
            "tencent",
            "timeweb",
            "transip",
            "udr",
            "ultra",
            "unoeuro",
            "variomedia",
            "veesp",
            "vercel",
            "vscale",
            "vultr",
            "websupport",
            "west_cn",
            "world4you",
            "yandex360",
            "yc",
            "zilore",
            "zone",
            "zoneedit",
            "zonomi"
          ],
          "optional": 1,
          "type": "string"
        },
        "data": {
          "description": "DNS plugin data. (base64 encoded)",
          "optional": 1,
          "type": "string"
        },
        "digest": {
          "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
          "maxLength": 64,
          "optional": 1,
          "type": "string"
        },
        "disable": {
          "description": "Flag to disable the config.",
          "optional": 1,
          "type": "boolean"
        },
        "nodes": {
          "description": "List of cluster node names.",
          "format": "pve-node-list",
          "optional": 1,
          "type": "string"
        },
        "plugin": {
          "description": "Unique identifier for ACME plugin instance.",
          "format": "pve-configid",
          "type": "string"
        },
        "type": {
          "description": "ACME challenge type.",
          "enum": [
            "dns",
            "standalone"
          ],
          "type": "string"
        },
        "validation-delay": {
          "default": 30,
          "description": "Extra delay in seconds to wait before requesting validation. Allows to cope with a long TTL of DNS records.",
          "maximum": 172800,
          "minimum": 0,
          "optional": 1,
          "type": "integer"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{plugin}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
