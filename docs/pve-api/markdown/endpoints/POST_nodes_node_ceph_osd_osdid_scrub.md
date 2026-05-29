# POST /nodes/{node}/ceph/osd/{osdid}/scrub

Instruct the OSD to scrub.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| osdid | integer | yes | OSD ID |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| deep | boolean | no | If set, instructs a deep scrub instead of a normal one. |

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
  "description": "Instruct the OSD to scrub.",
  "method": "POST",
  "name": "scrub",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "deep": {
        "default": 0,
        "description": "If set, instructs a deep scrub instead of a normal one.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "osdid": {
        "description": "OSD ID",
        "type": "integer",
        "typetext": "<integer>"
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
