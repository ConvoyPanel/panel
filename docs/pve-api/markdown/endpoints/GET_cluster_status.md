# GET /cluster/status

Get cluster status information.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "id": {
        "type": "string"
      },
      "ip": {
        "description": "[node] IP of the resolved nodename.",
        "optional": 1,
        "type": "string"
      },
      "level": {
        "description": "[node] Proxmox VE Subscription level, indicates if eligible for enterprise support as well as access to the stable Proxmox VE Enterprise Repository.",
        "optional": 1,
        "type": "string"
      },
      "local": {
        "description": "[node] Indicates if this is the responding node.",
        "optional": 1,
        "type": "boolean"
      },
      "name": {
        "type": "string"
      },
      "nodeid": {
        "description": "[node] ID of the node from the corosync configuration.",
        "optional": 1,
        "type": "integer"
      },
      "nodes": {
        "description": "[cluster] Nodes count, including offline nodes.",
        "optional": 1,
        "type": "integer"
      },
      "online": {
        "description": "[node] Indicates if the node is online or offline.",
        "optional": 1,
        "type": "boolean"
      },
      "quorate": {
        "description": "[cluster] Indicates if there is a majority of nodes online to make decisions",
        "optional": 1,
        "type": "boolean"
      },
      "type": {
        "description": "Indicates the type, either cluster or node. The type defines the object properties e.g. quorate available for type cluster.",
        "enum": [
          "cluster",
          "node"
        ],
        "type": "string"
      },
      "version": {
        "description": "[cluster] Current version of the corosync configuration file.",
        "optional": 1,
        "type": "integer"
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
  "description": "Get cluster status information.",
  "method": "GET",
  "name": "get_status",
  "parameters": {
    "additionalProperties": 0
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
  "protected": 1,
  "returns": {
    "items": {
      "properties": {
        "id": {
          "type": "string"
        },
        "ip": {
          "description": "[node] IP of the resolved nodename.",
          "optional": 1,
          "type": "string"
        },
        "level": {
          "description": "[node] Proxmox VE Subscription level, indicates if eligible for enterprise support as well as access to the stable Proxmox VE Enterprise Repository.",
          "optional": 1,
          "type": "string"
        },
        "local": {
          "description": "[node] Indicates if this is the responding node.",
          "optional": 1,
          "type": "boolean"
        },
        "name": {
          "type": "string"
        },
        "nodeid": {
          "description": "[node] ID of the node from the corosync configuration.",
          "optional": 1,
          "type": "integer"
        },
        "nodes": {
          "description": "[cluster] Nodes count, including offline nodes.",
          "optional": 1,
          "type": "integer"
        },
        "online": {
          "description": "[node] Indicates if the node is online or offline.",
          "optional": 1,
          "type": "boolean"
        },
        "quorate": {
          "description": "[cluster] Indicates if there is a majority of nodes online to make decisions",
          "optional": 1,
          "type": "boolean"
        },
        "type": {
          "description": "Indicates the type, either cluster or node. The type defines the object properties e.g. quorate available for type cluster.",
          "enum": [
            "cluster",
            "node"
          ],
          "type": "string"
        },
        "version": {
          "description": "[cluster] Current version of the corosync configuration file.",
          "optional": 1,
          "type": "integer"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
