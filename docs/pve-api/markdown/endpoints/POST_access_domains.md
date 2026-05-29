# POST /access/domains

Add an authentication server.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| realm | string | yes | Authentication domain ID |
| type | string | yes | Realm type. |
| acr-values | string | no | Specifies the Authentication Context Class Reference values that theAuthorization Server is being requested to use for the Auth Request. |
| audiences | string | no | A list of audiences that the OpenID Issuer may include that are accepted in addition to 'client-id'. |
| autocreate | boolean | no | Automatically create users if they do not exist. |
| base_dn | string | no | LDAP base domain name |
| bind_dn | string | no | LDAP bind domain name |
| capath | string | no | Path to the CA certificate store |
| case-sensitive | boolean | no | username is case-sensitive |
| cert | string | no | Path to the client certificate |
| certkey | string | no | Path to the client certificate key |
| check-connection | boolean | no | Check bind connection to the server. |
| client-id | string | no | OpenID Client ID |
| client-key | string | no | OpenID Client Key |
| comment | string | no | Description. |
| default | boolean | no | Use this as default realm |
| domain | string | no | AD domain name |
| filter | string | no | LDAP filter for user sync. |
| group_classes | string | no | The objectclasses for groups. |
| group_dn | string | no | LDAP base domain name for group sync. If not set, the base_dn will be used. |
| group_filter | string | no | LDAP filter for group sync. |
| group_name_attr | string | no | LDAP attribute representing a groups name. If not set or found, the first value of the DN will be used as name. |
| groups-autocreate | boolean | no | Automatically create groups if they do not exist. |
| groups-claim | string | no | OpenID claim used to retrieve groups with. |
| groups-overwrite | boolean | no | All groups will be overwritten for the user on login. |
| issuer-url | string | no | OpenID Issuer Url |
| mode | string | no | LDAP protocol mode. |
| password | string | no | LDAP bind password. Will be stored in '/etc/pve/priv/realm/<REALM>.pw'. |
| port | integer | no | Server port. |
| prompt | string | no | Specifies whether the Authorization Server prompts the End-User for reauthentication and consent. |
| query-userinfo | boolean | no | Enables querying the userinfo endpoint for claims values. |
| scopes | string | no | Specifies the scopes (user details) that should be authorized and returned, for example 'email' or 'profile'. |
| secure | boolean | no | Use secure LDAPS protocol. DEPRECATED: use 'mode' instead. |
| server1 | string | no | Server IP address (or DNS name) |
| server2 | string | no | Fallback Server IP address (or DNS name) |
| sslversion | string | no | LDAPS TLS/SSL version. It's not recommended to use version older than 1.2! |
| sync_attributes | string | no | Comma separated list of key=value pairs for specifying which LDAP attributes map to which PVE user field. For example, to map the LDAP attribute 'mail' to PVEs 'email', write  'email=mail'. By default, each PVE user field is represented  by an LDAP attribute of the same name. |
| sync-defaults-options | string | no | The default options for behavior of synchronizations. |
| tfa | string | no | Use Two-factor authentication. |
| user_attr | string | no | LDAP user attribute name |
| user_classes | string | no | The objectclasses for users. |
| username-claim | string | no | OpenID claim used to generate the unique username. |
| verify | boolean | no | Verify the server's SSL certificate |

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
    "/access/realm",
    [
      "Realm.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Add an authentication server.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "acr-values": {
        "description": "Specifies the Authentication Context Class Reference values that theAuthorization Server is being requested to use for the Auth Request.",
        "optional": 1,
        "pattern": "^[^\\x00-\\x1F\\x7F <>#\"]*$",
        "type": "string"
      },
      "audiences": {
        "description": "A list of audiences that the OpenID Issuer may include that are accepted in addition to 'client-id'.",
        "optional": 1,
        "pattern": "^[^\\x00-\\x1F\\x7F <>#\"]*$",
        "type": "string"
      },
      "autocreate": {
        "default": 0,
        "description": "Automatically create users if they do not exist.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "base_dn": {
        "description": "LDAP base domain name",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "bind_dn": {
        "description": "LDAP bind domain name",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "capath": {
        "default": "/etc/ssl/certs",
        "description": "Path to the CA certificate store",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "case-sensitive": {
        "default": 1,
        "description": "username is case-sensitive",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "cert": {
        "description": "Path to the client certificate",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "certkey": {
        "description": "Path to the client certificate key",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "check-connection": {
        "default": 0,
        "description": "Check bind connection to the server.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "client-id": {
        "description": "OpenID Client ID",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "client-key": {
        "description": "OpenID Client Key",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "comment": {
        "description": "Description.",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "default": {
        "description": "Use this as default realm",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "domain": {
        "description": "AD domain name",
        "maxLength": 256,
        "optional": 1,
        "pattern": "\\S+",
        "type": "string"
      },
      "filter": {
        "description": "LDAP filter for user sync.",
        "maxLength": 2048,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "group_classes": {
        "default": "groupOfNames, group, univentionGroup, ipausergroup",
        "description": "The objectclasses for groups.",
        "format": "ldap-simple-attr-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "group_dn": {
        "description": "LDAP base domain name for group sync. If not set, the base_dn will be used.",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "group_filter": {
        "description": "LDAP filter for group sync.",
        "maxLength": 2048,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "group_name_attr": {
        "description": "LDAP attribute representing a groups name. If not set or found, the first value of the DN will be used as name.",
        "format": "ldap-simple-attr",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "groups-autocreate": {
        "default": 0,
        "description": "Automatically create groups if they do not exist.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "groups-claim": {
        "description": "OpenID claim used to retrieve groups with.",
        "maxLength": 256,
        "optional": 1,
        "pattern": "(?^:[A-Za-z0-9\\.\\-_]+)",
        "type": "string"
      },
      "groups-overwrite": {
        "default": 0,
        "description": "All groups will be overwritten for the user on login.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "issuer-url": {
        "description": "OpenID Issuer Url",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "mode": {
        "default": "ldap",
        "description": "LDAP protocol mode.",
        "enum": [
          "ldap",
          "ldaps",
          "ldap+starttls"
        ],
        "optional": 1,
        "type": "string"
      },
      "password": {
        "description": "LDAP bind password. Will be stored in '/etc/pve/priv/realm/<REALM>.pw'.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "description": "Server port.",
        "maximum": 65535,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 65535)"
      },
      "prompt": {
        "description": "Specifies whether the Authorization Server prompts the End-User for reauthentication and consent.",
        "optional": 1,
        "pattern": "(?:none|login|consent|select_account|\\S+)",
        "type": "string"
      },
      "query-userinfo": {
        "default": 1,
        "description": "Enables querying the userinfo endpoint for claims values.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "realm": {
        "description": "Authentication domain ID",
        "format": "pve-realm",
        "maxLength": 32,
        "type": "string",
        "typetext": "<string>"
      },
      "scopes": {
        "default": "email profile",
        "description": "Specifies the scopes (user details) that should be authorized and returned, for example 'email' or 'profile'.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "secure": {
        "description": "Use secure LDAPS protocol. DEPRECATED: use 'mode' instead.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "server1": {
        "description": "Server IP address (or DNS name)",
        "format": "address",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "server2": {
        "description": "Fallback Server IP address (or DNS name)",
        "format": "address",
        "maxLength": 256,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "sslversion": {
        "description": "LDAPS TLS/SSL version. It's not recommended to use version older than 1.2!",
        "enum": [
          "tlsv1",
          "tlsv1_1",
          "tlsv1_2",
          "tlsv1_3"
        ],
        "optional": 1,
        "type": "string"
      },
      "sync-defaults-options": {
        "description": "The default options for behavior of synchronizations.",
        "format": "realm-sync-options",
        "optional": 1,
        "type": "string",
        "typetext": "[enable-new=<1|0>] [,full=<1|0>] [,purge=<1|0>] [,remove-vanished=([acl];[properties];[entry])|none] [,scope=<users|groups|both>]"
      },
      "sync_attributes": {
        "description": "Comma separated list of key=value pairs for specifying which LDAP attributes map to which PVE user field. For example, to map the LDAP attribute 'mail' to PVEs 'email', write  'email=mail'. By default, each PVE user field is represented  by an LDAP attribute of the same name.",
        "optional": 1,
        "pattern": "\\w+=[^,]+(,\\s*\\w+=[^,]+)*",
        "type": "string"
      },
      "tfa": {
        "description": "Use Two-factor authentication.",
        "format": "pve-tfa-config",
        "maxLength": 128,
        "optional": 1,
        "type": "string",
        "typetext": "type=<TFATYPE> [,digits=<COUNT>] [,id=<ID>] [,key=<KEY>] [,step=<SECONDS>] [,url=<URL>]"
      },
      "type": {
        "description": "Realm type.",
        "enum": [
          "ad",
          "ldap",
          "openid",
          "pam",
          "pve"
        ],
        "type": "string"
      },
      "user_attr": {
        "description": "LDAP user attribute name",
        "maxLength": 256,
        "optional": 1,
        "pattern": "\\S{2,}",
        "type": "string"
      },
      "user_classes": {
        "default": "inetorgperson, posixaccount, person, user",
        "description": "The objectclasses for users.",
        "format": "ldap-simple-attr-list",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "username-claim": {
        "description": "OpenID claim used to generate the unique username.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "verify": {
        "default": 0,
        "description": "Verify the server's SSL certificate",
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
      "/access/realm",
      [
        "Realm.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
