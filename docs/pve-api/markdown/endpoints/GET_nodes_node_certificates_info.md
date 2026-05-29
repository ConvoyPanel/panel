# GET /nodes/{node}/certificates/info

Get information about node's certificates.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "filename": {
        "optional": 1,
        "type": "string"
      },
      "fingerprint": {
        "description": "Certificate SHA 256 fingerprint.",
        "optional": 1,
        "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
        "type": "string"
      },
      "issuer": {
        "description": "Certificate issuer name.",
        "optional": 1,
        "type": "string"
      },
      "notafter": {
        "description": "Certificate's notAfter timestamp (UNIX epoch).",
        "optional": 1,
        "renderer": "timestamp",
        "type": "integer"
      },
      "notbefore": {
        "description": "Certificate's notBefore timestamp (UNIX epoch).",
        "optional": 1,
        "renderer": "timestamp",
        "type": "integer"
      },
      "pem": {
        "description": "Certificate in PEM format",
        "format": "pem-certificate",
        "optional": 1,
        "type": "string"
      },
      "public-key-bits": {
        "description": "Certificate's public key size",
        "optional": 1,
        "type": "integer"
      },
      "public-key-type": {
        "description": "Certificate's public key algorithm",
        "optional": 1,
        "type": "string"
      },
      "san": {
        "description": "List of Certificate's SubjectAlternativeName entries.",
        "items": {
          "type": "string"
        },
        "optional": 1,
        "renderer": "yaml",
        "type": "array"
      },
      "subject": {
        "description": "Certificate subject name.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
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
  "description": "Get information about node's certificates.",
  "method": "GET",
  "name": "info",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "filename": {
          "optional": 1,
          "type": "string"
        },
        "fingerprint": {
          "description": "Certificate SHA 256 fingerprint.",
          "optional": 1,
          "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
          "type": "string"
        },
        "issuer": {
          "description": "Certificate issuer name.",
          "optional": 1,
          "type": "string"
        },
        "notafter": {
          "description": "Certificate's notAfter timestamp (UNIX epoch).",
          "optional": 1,
          "renderer": "timestamp",
          "type": "integer"
        },
        "notbefore": {
          "description": "Certificate's notBefore timestamp (UNIX epoch).",
          "optional": 1,
          "renderer": "timestamp",
          "type": "integer"
        },
        "pem": {
          "description": "Certificate in PEM format",
          "format": "pem-certificate",
          "optional": 1,
          "type": "string"
        },
        "public-key-bits": {
          "description": "Certificate's public key size",
          "optional": 1,
          "type": "integer"
        },
        "public-key-type": {
          "description": "Certificate's public key algorithm",
          "optional": 1,
          "type": "string"
        },
        "san": {
          "description": "List of Certificate's SubjectAlternativeName entries.",
          "items": {
            "type": "string"
          },
          "optional": 1,
          "renderer": "yaml",
          "type": "array"
        },
        "subject": {
          "description": "Certificate subject name.",
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
