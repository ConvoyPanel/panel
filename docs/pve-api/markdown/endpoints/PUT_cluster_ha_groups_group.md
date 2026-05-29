# PUT /cluster/ha/groups/{group}

Update ha group configuration. (deprecated in favor of HA rules)

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| group | string | yes | The HA group identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| comment | string | no | Description. |
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| nodes | string | no | List of cluster node names with optional priority. |
| nofailback | boolean | no | The CRM tries to run services on the node with the highest priority. If a node with higher priority comes online, the CRM migrates the service to that node. Enabling nofailback prevents that behavior. |
| restricted | boolean | no | Resources bound to restricted groups may only run on nodes defined by the group. |

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
  "description": "Update ha group configuration. (deprecated in favor of HA rules)",
  "method": "PUT",
  "name": "update",
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
      "group": {
        "description": "The HA group identifier.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "nodes": {
        "description": "List of cluster node names with optional priority.",
        "format": "pve-ha-node-list",
        "optional": 1,
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
