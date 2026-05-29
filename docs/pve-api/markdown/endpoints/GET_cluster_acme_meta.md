# GET /cluster/acme/meta

Retrieve ACME Directory Meta Information

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| directory | string | no | URL of ACME CA directory endpoint. |

## Returns

```json
{
  "additionalProperties": 1,
  "properties": {
    "caaIdentities": {
      "description": "Hostnames referring to the ACME servers.",
      "items": {
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "externalAccountRequired": {
      "description": "EAB Required",
      "optional": 1,
      "type": "boolean"
    },
    "termsOfService": {
      "description": "ACME TermsOfService URL.",
      "optional": 1,
      "type": "string"
    },
    "website": {
      "description": "URL to more information about the ACME server.",
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
    "/nodes/{node}",
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
  "description": "Retrieve ACME Directory Meta Information",
  "method": "GET",
  "name": "get_meta",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "directory": {
        "default": "https://acme-v02.api.letsencrypt.org/directory",
        "description": "URL of ACME CA directory endpoint.",
        "optional": 1,
        "pattern": "^https?://.*",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "additionalProperties": 1,
    "properties": {
      "caaIdentities": {
        "description": "Hostnames referring to the ACME servers.",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "externalAccountRequired": {
        "description": "EAB Required",
        "optional": 1,
        "type": "boolean"
      },
      "termsOfService": {
        "description": "ACME TermsOfService URL.",
        "optional": 1,
        "type": "string"
      },
      "website": {
        "description": "URL to more information about the ACME server.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
