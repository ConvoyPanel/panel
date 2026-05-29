# PUT /cluster/acme/plugins/{id}

Update ACME plugin configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | ACME Plugin ID name |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| api | string | no | API plugin name |
| data | string | no | DNS plugin data. (base64 encoded) |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| disable | boolean | no | Flag to disable the config. |
| nodes | string | no | List of cluster node names. |
| validation-delay | integer | no | Extra delay in seconds to wait before requesting validation. Allows to cope with a long TTL of DNS records. |

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
  "description": "Update ACME plugin configuration.",
  "method": "PUT",
  "name": "update_plugin",
  "parameters": {
    "additionalProperties": 0,
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
        "description": "Flag to disable the config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "id": {
        "description": "ACME Plugin ID name",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "nodes": {
        "description": "List of cluster node names.",
        "format": "pve-node-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "validation-delay": {
        "default": 30,
        "description": "Extra delay in seconds to wait before requesting validation. Allows to cope with a long TTL of DNS records.",
        "maximum": 172800,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 172800)"
      }
    },
    "type": "object"
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
