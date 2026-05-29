# /access

Endpoints in the `/access` section.

| Method | Path | Summary |
|---|---|---|
| GET | `/access` | [index](endpoints/GET_access.md) |
| GET | `/access/acl` | [read_acl](endpoints/GET_access_acl.md) |
| PUT | `/access/acl` | [update_acl](endpoints/PUT_access_acl.md) |
| GET | `/access/domains` | [index](endpoints/GET_access_domains.md) |
| POST | `/access/domains` | [create](endpoints/POST_access_domains.md) |
| DELETE | `/access/domains/{realm}` | [delete](endpoints/DELETE_access_domains_realm.md) |
| GET | `/access/domains/{realm}` | [read](endpoints/GET_access_domains_realm.md) |
| PUT | `/access/domains/{realm}` | [update](endpoints/PUT_access_domains_realm.md) |
| POST | `/access/domains/{realm}/sync` | [sync](endpoints/POST_access_domains_realm_sync.md) |
| GET | `/access/groups` | [index](endpoints/GET_access_groups.md) |
| POST | `/access/groups` | [create_group](endpoints/POST_access_groups.md) |
| DELETE | `/access/groups/{groupid}` | [delete_group](endpoints/DELETE_access_groups_groupid.md) |
| GET | `/access/groups/{groupid}` | [read_group](endpoints/GET_access_groups_groupid.md) |
| PUT | `/access/groups/{groupid}` | [update_group](endpoints/PUT_access_groups_groupid.md) |
| GET | `/access/openid` | [index](endpoints/GET_access_openid.md) |
| POST | `/access/openid/auth-url` | [auth_url](endpoints/POST_access_openid_auth_url.md) |
| POST | `/access/openid/login` | [login](endpoints/POST_access_openid_login.md) |
| PUT | `/access/password` | [change_password](endpoints/PUT_access_password.md) |
| GET | `/access/permissions` | [permissions](endpoints/GET_access_permissions.md) |
| GET | `/access/roles` | [index](endpoints/GET_access_roles.md) |
| POST | `/access/roles` | [create_role](endpoints/POST_access_roles.md) |
| DELETE | `/access/roles/{roleid}` | [delete_role](endpoints/DELETE_access_roles_roleid.md) |
| GET | `/access/roles/{roleid}` | [read_role](endpoints/GET_access_roles_roleid.md) |
| PUT | `/access/roles/{roleid}` | [update_role](endpoints/PUT_access_roles_roleid.md) |
| GET | `/access/tfa` | [list_tfa](endpoints/GET_access_tfa.md) |
| GET | `/access/tfa/{userid}` | [list_user_tfa](endpoints/GET_access_tfa_userid.md) |
| POST | `/access/tfa/{userid}` | [add_tfa_entry](endpoints/POST_access_tfa_userid.md) |
| DELETE | `/access/tfa/{userid}/{id}` | [delete_tfa](endpoints/DELETE_access_tfa_userid_id.md) |
| GET | `/access/tfa/{userid}/{id}` | [get_tfa_entry](endpoints/GET_access_tfa_userid_id.md) |
| PUT | `/access/tfa/{userid}/{id}` | [update_tfa_entry](endpoints/PUT_access_tfa_userid_id.md) |
| GET | `/access/ticket` | [get_ticket](endpoints/GET_access_ticket.md) |
| POST | `/access/ticket` | [create_ticket](endpoints/POST_access_ticket.md) |
| GET | `/access/users` | [index](endpoints/GET_access_users.md) |
| POST | `/access/users` | [create_user](endpoints/POST_access_users.md) |
| DELETE | `/access/users/{userid}` | [delete_user](endpoints/DELETE_access_users_userid.md) |
| GET | `/access/users/{userid}` | [read_user](endpoints/GET_access_users_userid.md) |
| PUT | `/access/users/{userid}` | [update_user](endpoints/PUT_access_users_userid.md) |
| GET | `/access/users/{userid}/tfa` | [read_user_tfa_type](endpoints/GET_access_users_userid_tfa.md) |
| GET | `/access/users/{userid}/token` | [token_index](endpoints/GET_access_users_userid_token.md) |
| DELETE | `/access/users/{userid}/token/{tokenid}` | [remove_token](endpoints/DELETE_access_users_userid_token_tokenid.md) |
| GET | `/access/users/{userid}/token/{tokenid}` | [read_token](endpoints/GET_access_users_userid_token_tokenid.md) |
| POST | `/access/users/{userid}/token/{tokenid}` | [generate_token](endpoints/POST_access_users_userid_token_tokenid.md) |
| PUT | `/access/users/{userid}/token/{tokenid}` | [update_token_info](endpoints/PUT_access_users_userid_token_tokenid.md) |
| PUT | `/access/users/{userid}/unlock-tfa` | [unlock_tfa](endpoints/PUT_access_users_userid_unlock_tfa.md) |
| POST | `/access/vncticket` | [verify_vnc_ticket](endpoints/POST_access_vncticket.md) |
