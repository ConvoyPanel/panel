<?php

namespace App\Enums\Audit;

/**
 * The audit event catalog. Every audited action in the panel has exactly one case here.
 *
 * Values are `<area>.<thing>.<verb>` and are a **stable API** — they are written to the database,
 * returned by the client and admin audit endpoints, and matched exhaustively by the frontend copy
 * map. Renaming a value orphans every historical row that carries it, so treat these as immutable
 * once shipped; the human-readable wording lives on the frontend precisely so it can change freely
 * without touching stored data.
 *
 * This enum is transformed into a TypeScript string union in `resources/scripts/types/generated.d.ts`,
 * which is what makes the frontend's `Record<AuditEvent, ...>` copy map fail to compile when a case
 * is added here without matching wording.
 *
 * Adding an event: add the case, and only touch {@see self::retention()} or {@see self::visibility()}
 * if it needs something other than the default (pruned on the standard window, visible to clients).
 */
enum AuditEvent: string
{
    // -----------------------------------------------------------------------------------------
    // Authentication. Subject is the User being authenticated (null on a failed login where no
    // account matched). These are the events an operator reaches for after a compromise.
    // -----------------------------------------------------------------------------------------
    // Recorded from Laravel's own auth events rather than call sites, because the controllers
    // behind them belong to Fortify. Deliberately only three: a passkey login, a completed
    // two-factor challenge and an identity re-confirmation all end in Auth::login(), so giving
    // them their own cases would double-count a single sign-in.
    case AUTH_LOGIN_SUCCEEDED = 'auth.login.succeeded';
    case AUTH_LOGIN_FAILED = 'auth.login.failed';
    case AUTH_LOGOUT = 'auth.logout';

    // -----------------------------------------------------------------------------------------
    // Account and credential management. Subject is the User.
    // -----------------------------------------------------------------------------------------
    case ACCOUNT_PASSWORD_UPDATED = 'account.password.updated';
    case ACCOUNT_TWO_FACTOR_ENABLED = 'account.two-factor.enabled';
    case ACCOUNT_TWO_FACTOR_CONFIRMED = 'account.two-factor.confirmed';
    case ACCOUNT_TWO_FACTOR_DISABLED = 'account.two-factor.disabled';
    case ACCOUNT_RECOVERY_CODES_REGENERATED = 'account.recovery-codes.regenerated';
    case ACCOUNT_PASSKEY_CREATED = 'account.passkey.created';
    case ACCOUNT_PASSKEY_RENAMED = 'account.passkey.renamed';
    case ACCOUNT_PASSKEY_DELETED = 'account.passkey.deleted';
    case ACCOUNT_SSH_KEY_CREATED = 'account.ssh-key.created';
    case ACCOUNT_SSH_KEY_DELETED = 'account.ssh-key.deleted';
    case ACCOUNT_API_KEY_CREATED = 'account.api-key.created';
    case ACCOUNT_API_KEY_DELETED = 'account.api-key.deleted';
    case ACCOUNT_SESSION_REVOKED = 'account.session.revoked';
    case ACCOUNT_OAUTH_CONNECTION_DELETED = 'account.oauth-connection.deleted';

    // -----------------------------------------------------------------------------------------
    // Client-side server actions. Subject is the Server. These are the bulk of #53.
    // -----------------------------------------------------------------------------------------
    // One event carrying the PowerCommand as a property, rather than a case per signal. There are
    // seven signals and an admin mirror of each; enumerating them would mean fourteen cases and a
    // catalog change every time PowerCommand grows. The frontend renders this one key through an
    // exhaustive map over PowerCommand, which is itself a generated TS union.
    case SERVER_POWER_SENT = 'server.power.sent';
    case SERVER_REINSTALLED = 'server.reinstalled';
    case SERVER_INSTALLATION_RETRIED = 'server.installation.retried';
    case SERVER_RENAMED = 'server.renamed';
    case SERVER_CONSOLE_SESSION_CREATED = 'server.console.session-created';
    case SERVER_CONSOLE_DISPLAY_ENABLED = 'server.console.display-enabled';
    case SERVER_CONSOLE_SERIAL_ENABLED = 'server.console.serial-enabled';
    case SERVER_BACKUP_CREATED = 'server.backup.created';
    case SERVER_BACKUP_DELETED = 'server.backup.deleted';
    case SERVER_BACKUP_RESTORED = 'server.backup.restored';
    case SERVER_FIREWALL_OPTIONS_UPDATED = 'server.firewall.options-updated';
    case SERVER_FIREWALL_RULE_CREATED = 'server.firewall.rule-created';
    case SERVER_FIREWALL_RULE_UPDATED = 'server.firewall.rule-updated';
    case SERVER_FIREWALL_RULE_DELETED = 'server.firewall.rule-deleted';
    case SERVER_FIREWALL_RULE_MOVED = 'server.firewall.rule-moved';
    case SERVER_AUTH_SETTINGS_UPDATED = 'server.settings.auth-updated';
    case SERVER_BOOT_ORDER_UPDATED = 'server.settings.boot-order-updated';
    case SERVER_NETWORK_SETTINGS_UPDATED = 'server.settings.network-updated';
    case SERVER_MEDIA_MOUNTED = 'server.media.mounted';
    case SERVER_MEDIA_UNMOUNTED = 'server.media.unmounted';

    // -----------------------------------------------------------------------------------------
    // Administrative actions on a server. Subject is the Server, so these surface in the owning
    // client's activity feed too — deliberately, since they are things done *to* their server.
    // -----------------------------------------------------------------------------------------
    case ADMIN_SERVER_CREATED = 'admin.server.created';
    case ADMIN_SERVER_UPDATED = 'admin.server.updated';
    case ADMIN_SERVER_DELETED = 'admin.server.deleted';
    case ADMIN_SERVER_POWER_SENT = 'admin.server.power-sent';
    case ADMIN_SERVER_BUILD_UPDATED = 'admin.server.build-updated';
    case ADMIN_SERVER_SUSPENDED = 'admin.server.suspended';
    case ADMIN_SERVER_UNSUSPENDED = 'admin.server.unsuspended';
    case ADMIN_SERVER_DISK_CREATED = 'admin.server.disk-created';
    case ADMIN_SERVER_DISK_UPDATED = 'admin.server.disk-updated';
    case ADMIN_SERVER_DISK_DELETED = 'admin.server.disk-deleted';
    // Recorded by the placement reconciler when PVE moved the guest (HA
    // recovery, migration) and Convoy followed. Actor is the SystemActor.
    case ADMIN_SERVER_REHOMED = 'admin.server.rehomed';
    case ADMIN_BACKUP_DELETED = 'admin.backup.deleted';

    // -----------------------------------------------------------------------------------------
    // Infrastructure. Subject is the Node or the nested resource. Never client-visible.
    // -----------------------------------------------------------------------------------------
    case ADMIN_NODE_CREATED = 'admin.node.created';
    case ADMIN_NODE_UPDATED = 'admin.node.updated';
    case ADMIN_NODE_DELETED = 'admin.node.deleted';
    case ADMIN_NODE_ISO_CREATED = 'admin.node.iso-created';
    case ADMIN_NODE_ISO_UPDATED = 'admin.node.iso-updated';
    case ADMIN_NODE_ISO_DELETED = 'admin.node.iso-deleted';
    case ADMIN_NODE_INTERFACE_CREATED = 'admin.node.interface-created';
    case ADMIN_NODE_INTERFACE_UPDATED = 'admin.node.interface-updated';
    case ADMIN_NODE_INTERFACE_DELETED = 'admin.node.interface-deleted';
    case ADMIN_NODE_VLAN_CREATED = 'admin.node.vlan-created';
    case ADMIN_NODE_VLAN_UPDATED = 'admin.node.vlan-updated';
    case ADMIN_NODE_VLAN_DELETED = 'admin.node.vlan-deleted';
    case ADMIN_NODE_STORAGE_CREATED = 'admin.node.storage-created';
    case ADMIN_NODE_STORAGE_UPDATED = 'admin.node.storage-updated';
    case ADMIN_NODE_STORAGE_DELETED = 'admin.node.storage-deleted';
    case ADMIN_NODE_STORAGE_BACKUP_ORDER_UPDATED = 'admin.node.storage-backup-order-updated';
    // The operator cleared the cluster identity tripwire (see ClusterIdentityService).
    case ADMIN_CLUSTER_UNFLAGGED = 'admin.cluster.unflagged';
    case ADMIN_LOCATION_CREATED = 'admin.location.created';
    case ADMIN_LOCATION_UPDATED = 'admin.location.updated';
    case ADMIN_LOCATION_DELETED = 'admin.location.deleted';
    case ADMIN_ANCHOR_CREATED = 'admin.anchor.created';
    case ADMIN_ANCHOR_UPDATED = 'admin.anchor.updated';
    case ADMIN_ANCHOR_DELETED = 'admin.anchor.deleted';
    case ADMIN_ANCHOR_ENROLLMENT_ROTATED = 'admin.anchor.enrollment-rotated';

    // -----------------------------------------------------------------------------------------
    // IP address management. Subject is the block group, block, or address.
    // -----------------------------------------------------------------------------------------
    case ADMIN_ADDRESS_BLOCK_GROUP_CREATED = 'admin.address-block-group.created';
    case ADMIN_ADDRESS_BLOCK_GROUP_UPDATED = 'admin.address-block-group.updated';
    case ADMIN_ADDRESS_BLOCK_GROUP_DELETED = 'admin.address-block-group.deleted';
    case ADMIN_ADDRESS_BLOCK_GROUP_NODE_ATTACHED = 'admin.address-block-group.node-attached';
    case ADMIN_ADDRESS_BLOCK_GROUP_NODE_DETACHED = 'admin.address-block-group.node-detached';
    case ADMIN_ADDRESS_BLOCK_CREATED = 'admin.address-block.created';
    case ADMIN_ADDRESS_BLOCK_UPDATED = 'admin.address-block.updated';
    case ADMIN_ADDRESS_BLOCK_DELETED = 'admin.address-block.deleted';
    case ADMIN_ADDRESS_GENERATED = 'admin.address.generated';
    case ADMIN_ADDRESS_UPDATED = 'admin.address.updated';
    case ADMIN_ADDRESS_DELETED = 'admin.address.deleted';
    case ADMIN_ADDRESS_RESERVED = 'admin.address.reserved';
    case ADMIN_ADDRESS_UNRESERVED = 'admin.address.unreserved';

    // -----------------------------------------------------------------------------------------
    // Users, tokens and panel configuration. Subject is the User, token, or null for settings.
    // -----------------------------------------------------------------------------------------
    case ADMIN_USER_CREATED = 'admin.user.created';
    case ADMIN_USER_UPDATED = 'admin.user.updated';
    case ADMIN_USER_DELETED = 'admin.user.deleted';
    case ADMIN_USER_SSO_TOKEN_GENERATED = 'admin.user.sso-token-generated';
    case ADMIN_TOKEN_CREATED = 'admin.token.created';
    case ADMIN_TOKEN_UPDATED = 'admin.token.updated';
    case ADMIN_TOKEN_DELETED = 'admin.token.deleted';
    case ADMIN_SETTINGS_ANCHOR_UPDATED = 'admin.settings.anchor-updated';
    case ADMIN_SETTINGS_BANDWIDTH_UPDATED = 'admin.settings.bandwidth-updated';

    // -----------------------------------------------------------------------------------------
    // Presets and templates. Subject is the preset, group, or template.
    // -----------------------------------------------------------------------------------------
    case ADMIN_SERVER_PRESET_CREATED = 'admin.server-preset.created';
    case ADMIN_SERVER_PRESET_UPDATED = 'admin.server-preset.updated';
    case ADMIN_SERVER_PRESET_DELETED = 'admin.server-preset.deleted';
    case ADMIN_TEMPLATE_GROUP_CREATED = 'admin.template-group.created';
    case ADMIN_TEMPLATE_GROUP_UPDATED = 'admin.template-group.updated';
    case ADMIN_TEMPLATE_GROUP_DELETED = 'admin.template-group.deleted';
    case ADMIN_TEMPLATE_CREATED = 'admin.template.created';
    case ADMIN_TEMPLATE_UPDATED = 'admin.template.updated';
    case ADMIN_TEMPLATE_DELETED = 'admin.template.deleted';

    /**
     * How long entries for this event survive. Defaults to the configured prune window; the listed
     * exceptions are kept forever because they are what a compromise investigation needs and they
     * are far too low-volume to be worth reclaiming.
     */
    public function retention(): AuditRetention
    {
        return match ($this) {
            self::AUTH_LOGIN_SUCCEEDED,
            self::AUTH_LOGIN_FAILED,
            self::AUTH_LOGOUT,
            self::ACCOUNT_PASSWORD_UPDATED,
            self::ACCOUNT_TWO_FACTOR_ENABLED,
            self::ACCOUNT_TWO_FACTOR_CONFIRMED,
            self::ACCOUNT_TWO_FACTOR_DISABLED,
            self::ACCOUNT_RECOVERY_CODES_REGENERATED,
            self::ACCOUNT_PASSKEY_CREATED,
            self::ACCOUNT_PASSKEY_RENAMED,
            self::ACCOUNT_PASSKEY_DELETED,
            self::ACCOUNT_SSH_KEY_CREATED,
            self::ACCOUNT_SSH_KEY_DELETED,
            self::ACCOUNT_API_KEY_CREATED,
            self::ACCOUNT_API_KEY_DELETED,
            self::ACCOUNT_SESSION_REVOKED,
            self::ACCOUNT_OAUTH_CONNECTION_DELETED,
            self::ADMIN_USER_CREATED,
            self::ADMIN_USER_UPDATED,
            self::ADMIN_USER_DELETED,
            self::ADMIN_USER_SSO_TOKEN_GENERATED,
            self::ADMIN_TOKEN_CREATED,
            self::ADMIN_TOKEN_UPDATED,
            self::ADMIN_TOKEN_DELETED,
            self::ADMIN_SERVER_DELETED,
            self::ADMIN_NODE_DELETED => AuditRetention::FOREVER,
            default => AuditRetention::STANDARD,
        };
    }

    /**
     * Whether this event may be shown to a non-admin who can see the subject.
     *
     * Most events need no entry here: infrastructure events are hidden in practice because their
     * subject is a Node or a token that no client can reach, and server events are things the
     * owner is entitled to see. The exceptions below are events whose subject *is* client-reachable
     * but whose existence should not be.
     */
    public function visibility(): AuditVisibility
    {
        return match ($this) {
            // Reveals that the panel minted a token capable of impersonating the user.
            self::ADMIN_USER_SSO_TOKEN_GENERATED => AuditVisibility::ADMIN_ONLY,
            // Names physical nodes; which host a VM lands on is infrastructure
            // detail a client has no lever over and no need to see.
            self::ADMIN_SERVER_REHOMED => AuditVisibility::ADMIN_ONLY,
            default => AuditVisibility::CLIENT,
        };
    }

    /** Every event that the pruner must never delete. */
    public static function retainedForever(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $event) => $event->retention() === AuditRetention::FOREVER,
        ));
    }
}
