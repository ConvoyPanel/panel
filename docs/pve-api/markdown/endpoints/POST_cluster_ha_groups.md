# POST /cluster/ha/groups

Create a new HA group. (deprecated in favor of HA rules)

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| group | string | yes | The HA group identifier. |
| nodes | string | yes | List of cluster node names with optional priority. |
| comment | string | no | Description. |
| nofailback | boolean | no | The CRM tries to run services on the node with the highest priority. If a node with higher priority comes online, the CRM migrates the service to that node. Enabling nofailback prevents that behavior. |
| restricted | boolean | no | Resources bound to restricted groups may only run on nodes defined by the group. |
| type | string | no | Group type. |

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
      "Sys.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create a new HA group. (deprecated in favor of HA rules)",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "description": "Description.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "group": {
        "description": "The HA group identifier.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "nodes": {
        "description": "List of cluster node names with optional priority.",
        "format": "pve-ha-node-list",
        "optional": 0,
        "type": "string",
        "typetext": "<node>[:<pri>]{,<node>[:<pri>]}*",
        "verbose_description": "List of cluster node members, where a priority can be given to each node. A resource will run on the available nodes with the highest priority. If there are more nodes in the highest priority class, the resources will get distributed to those nodes. The priorities have a relative meaning only. The higher the number, the higher the priority."
      },
      "nofailback": {
        "default": 0,
        "description": "The CRM tries to run services on the node with the highest priority. If a node with higher priority comes online, the CRM migrates the service to that node. Enabling nofailback prevents that behavior.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "restricted": {
        "default": 0,
        "description": "Resources bound to restricted groups may only run on nodes defined by the group.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>",
        "verbose_description": "Resources bound to restricted groups may only run on nodes defined by the group. The resource will be placed in the stopped state if no group node member is online. Resources on unrestricted groups may run on any cluster node if all group members are offline, but they will migrate back as soon as a group member comes online. One can implement a 'preferred node' behavior using an unrestricted group with only one member."
      },
      "type": {
        "description": "Group type.",
        "enum": [
          "group"
        ],
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Console"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
