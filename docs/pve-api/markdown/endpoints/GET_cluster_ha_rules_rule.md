# GET /cluster/ha/rules/{rule}

Read HA rule.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| rule | string | yes | HA rule identifier. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "rule": {
      "description": "HA rule identifier.",
      "format": "pve-configid",
      "type": "string"
    },
    "type": {
      "description": "HA rule type.",
      "enum": [
        "node-affinity",
        "resource-affinity"
      ],
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
  "description": "Read HA rule.",
  "method": "GET",
  "name": "read_rule",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "rule": {
        "description": "HA rule identifier.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
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
  "returns": {
    "properties": {
      "rule": {
        "description": "HA rule identifier.",
        "format": "pve-configid",
        "type": "string"
      },
      "type": {
        "description": "HA rule type.",
        "enum": [
          "node-affinity",
          "resource-affinity"
        ],
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
