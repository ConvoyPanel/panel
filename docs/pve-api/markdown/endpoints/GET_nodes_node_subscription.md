# GET /nodes/{node}/subscription

Read subscription info.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "checktime": {
      "description": "Timestamp of the last check done.",
      "optional": 1,
      "type": "integer"
    },
    "key": {
      "description": "The subscription key, if set and permitted to access.",
      "optional": 1,
      "type": "string"
    },
    "level": {
      "description": "A short code for the subscription level.",
      "optional": 1,
      "type": "string"
    },
    "message": {
      "description": "A more human readable status message.",
      "optional": 1,
      "type": "string"
    },
    "nextduedate": {
      "description": "Next due date of the set subscription.",
      "optional": 1,
      "type": "string"
    },
    "productname": {
      "description": "Human readable productname of the set subscription.",
      "optional": 1,
      "type": "string"
    },
    "regdate": {
      "description": "Register date of the set subscription.",
      "optional": 1,
      "type": "string"
    },
    "serverid": {
      "description": "The server ID, if permitted to access.",
      "optional": 1,
      "type": "string"
    },
    "signature": {
      "description": "Signature for offline keys",
      "optional": 1,
      "type": "string"
    },
    "sockets": {
      "description": "The number of sockets for this host.",
      "optional": 1,
      "type": "integer"
    },
    "status": {
      "description": "The current subscription status.",
      "enum": [
        "new",
        "notfound",
        "active",
        "invalid",
        "expired",
        "suspended"
      ],
      "type": "string"
    },
    "url": {
      "description": "URL to the web shop.",
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read subscription info.",
  "method": "GET",
  "name": "get",
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
    "additionalProperties": 0,
    "properties": {
      "checktime": {
        "description": "Timestamp of the last check done.",
        "optional": 1,
        "type": "integer"
      },
      "key": {
        "description": "The subscription key, if set and permitted to access.",
        "optional": 1,
        "type": "string"
      },
      "level": {
        "description": "A short code for the subscription level.",
        "optional": 1,
        "type": "string"
      },
      "message": {
        "description": "A more human readable status message.",
        "optional": 1,
        "type": "string"
      },
      "nextduedate": {
        "description": "Next due date of the set subscription.",
        "optional": 1,
        "type": "string"
      },
      "productname": {
        "description": "Human readable productname of the set subscription.",
        "optional": 1,
        "type": "string"
      },
      "regdate": {
        "description": "Register date of the set subscription.",
        "optional": 1,
        "type": "string"
      },
      "serverid": {
        "description": "The server ID, if permitted to access.",
        "optional": 1,
        "type": "string"
      },
      "signature": {
        "description": "Signature for offline keys",
        "optional": 1,
        "type": "string"
      },
      "sockets": {
        "description": "The number of sockets for this host.",
        "optional": 1,
        "type": "integer"
      },
      "status": {
        "description": "The current subscription status.",
        "enum": [
          "new",
          "notfound",
          "active",
          "invalid",
          "expired",
          "suspended"
        ],
        "type": "string"
      },
      "url": {
        "description": "URL to the web shop.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
