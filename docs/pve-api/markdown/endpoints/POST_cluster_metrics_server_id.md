# POST /cluster/metrics/server/{id}

Create a new external metric server config

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the entry. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| port | integer | yes | server network port |
| server | string | yes | server dns name or IP address |
| type | string | yes | Plugin type. |
| api-path-prefix | string | no | An API path prefix inserted between '<host>:<port>/' and '/api2/'. Can be useful if the InfluxDB service runs behind a reverse proxy. |
| bucket | string | no | The InfluxDB bucket/db. Only necessary when using the http v2 api. |
| disable | boolean | no | Flag to disable the plugin. |
| influxdbproto | string | no |  |
| max-body-size | integer | no | InfluxDB max-body-size in bytes. Requests are batched up to this size. |
| mtu | integer | no | MTU for metrics transmission over UDP |
| organization | string | no | The InfluxDB organization. Only necessary when using the http v2 api. Has no meaning when using v2 compatibility api. |
| otel-compression | string | no | Compression algorithm for requests |
| otel-headers | string | no | Custom HTTP headers (JSON format, base64 encoded) |
| otel-max-body-size | integer | no | Maximum request body size in bytes |
| otel-path | string | no | OTLP endpoint path |
| otel-protocol | string | no | HTTP protocol |
| otel-resource-attributes | string | no | Additional resource attributes as JSON, base64 encoded |
| otel-timeout | integer | no | HTTP request timeout in seconds |
| otel-verify-ssl | boolean | no | Verify SSL certificates |
| path | string | no | root graphite path (ex: proxmox.mycluster.mykey) |
| proto | string | no | Protocol to send graphite data. TCP or UDP (default) |
| timeout | integer | no | graphite TCP socket timeout (default=1) |
| token | string | no | The InfluxDB access token. Only necessary when using the http v2 api. If the v2 compatibility api is used, use 'user:password' instead. |
| verify-certificate | boolean | no | Set to 0 to disable certificate verification for https endpoints. |

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
  "description": "Create a new external metric server config",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "api-path-prefix": {
        "description": "An API path prefix inserted between '<host>:<port>/' and '/api2/'. Can be useful if the InfluxDB service runs behind a reverse proxy.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "bucket": {
        "description": "The InfluxDB bucket/db. Only necessary when using the http v2 api.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "disable": {
        "description": "Flag to disable the plugin.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "id": {
        "description": "The ID of the entry.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "influxdbproto": {
        "default": "udp",
        "enum": [
          "udp",
          "http",
          "https"
        ],
        "optional": 1,
        "type": "string"
      },
      "max-body-size": {
        "default": 25000000,
        "description": "InfluxDB max-body-size in bytes. Requests are batched up to this size.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      },
      "mtu": {
        "default": 1500,
        "description": "MTU for metrics transmission over UDP",
        "maximum": 65536,
        "minimum": 512,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (512 - 65536)"
      },
      "organization": {
        "description": "The InfluxDB organization. Only necessary when using the http v2 api. Has no meaning when using v2 compatibility api.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "otel-compression": {
        "default": "gzip",
        "description": "Compression algorithm for requests",
        "enum": [
          "none",
          "gzip"
        ],
        "optional": 1,
        "type": "string"
      },
      "otel-headers": {
        "description": "Custom HTTP headers (JSON format, base64 encoded)",
        "maxLength": 1024,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "otel-max-body-size": {
        "default": 10000000,
        "description": "Maximum request body size in bytes",
        "minimum": 1024,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1024 - N)"
      },
      "otel-path": {
        "default": "/v1/metrics",
        "description": "OTLP endpoint path",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "otel-protocol": {
        "default": "https",
        "description": "HTTP protocol",
        "enum": [
          "http",
          "https"
        ],
        "optional": 1,
        "type": "string"
      },
      "otel-resource-attributes": {
        "description": "Additional resource attributes as JSON, base64 encoded",
        "maxLength": 1024,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "otel-timeout": {
        "default": 5,
        "description": "HTTP request timeout in seconds",
        "maximum": 10,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 10)"
      },
      "otel-verify-ssl": {
        "default": 1,
        "description": "Verify SSL certificates",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "path": {
        "description": "root graphite path (ex: proxmox.mycluster.mykey)",
        "format": "graphite-path",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "description": "server network port",
        "maximum": 65536,
        "minimum": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 65536)"
      },
      "proto": {
        "description": "Protocol to send graphite data. TCP or UDP (default)",
        "enum": [
          "udp",
          "tcp"
        ],
        "optional": 1,
        "type": "string"
      },
      "server": {
        "description": "server dns name or IP address",
        "format": "address",
        "type": "string",
        "typetext": "<string>"
      },
      "timeout": {
        "default": 1,
        "description": "graphite TCP socket timeout (default=1)",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "token": {
        "description": "The InfluxDB access token. Only necessary when using the http v2 api. If the v2 compatibility api is used, use 'user:password' instead.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "Plugin type.",
        "enum": [
          "graphite",
          "influxdb",
          "opentelemetry"
        ],
        "format": "pve-configid",
        "type": "string"
      },
      "verify-certificate": {
        "default": 1,
        "description": "Set to 0 to disable certificate verification for https endpoints.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
