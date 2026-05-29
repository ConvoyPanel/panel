# POST /nodes/{node}/certificates/custom

Upload or update custom certificate chain and key.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| certificates | string | yes | PEM encoded certificate (chain). |
| force | boolean | no | Overwrite existing custom or ACME certificate files. |
| key | string | no | PEM encoded private key. |
| restart | boolean | no | Restart pveproxy. |

## Returns

```json
{
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
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
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
  "description": "Upload or update custom certificate chain and key.",
  "method": "POST",
  "name": "upload_custom_cert",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "certificates": {
        "description": "PEM encoded certificate (chain).",
        "format": "pem-certificate-chain",
        "type": "string",
        "typetext": "<string>"
      },
      "force": {
        "default": 0,
        "description": "Overwrite existing custom or ACME certificate files.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "key": {
        "description": "PEM encoded private key.",
        "format": "pem-string",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "restart": {
        "default": 0,
        "description": "Restart pveproxy.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
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
  }
}
```
