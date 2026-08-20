/**
 * The wording for every audit event.
 *
 * The backend stores only the event key and a properties bag, so all user-facing phrasing lives
 * here and can be reworded — or translated — without a migration. See docs/audit-log-plan.md.
 *
 * The map is typed `Record<App.Enums.Audit.AuditEvent, …>`, and that enum is generated from the
 * PHP catalog. Adding a case in PHP without adding wording here is therefore a **type error**, not
 * a row that silently renders as a raw dotted key.
 */

type AuditEvent = App.Enums.Audit.AuditEvent

export type AuditProperties = Record<string, unknown>

export interface AuditEventCopy {
    /** Reads directly after the actor's name: "Eric started the server". */
    verb: string
    /**
     * A short qualifier shown beside the sentence, or null when this entry has
     * nothing worth adding. Falls back to {@link defaultDetail}.
     */
    detail?: (properties: AuditProperties) => string | null
}

/** First present, non-empty value among `keys`, rendered as a string. */
const pick = (properties: AuditProperties, ...keys: string[]): string | null => {
    for (const key of keys) {
        const value = properties[key]

        if (typeof value === 'string' && value !== '') return value
        if (typeof value === 'number') return String(value)
    }

    return null
}

/**
 * What most events want: whatever human-readable identifier they happened to record. Events with
 * something better to say override `detail` below.
 */
const defaultDetail = (properties: AuditProperties): string | null =>
    pick(
        properties,
        'name',
        'template',
        'backup',
        'iso',
        'address',
        'base_ip',
        'short_code',
        'hostname',
        'provider',
        'node'
    )

/** "changed email and name" — the field list an update event records. */
const changedFields = (properties: AuditProperties): string | null => {
    const changed = properties.changed

    if (!Array.isArray(changed) || changed.length === 0) return null

    const fields = changed.map((field) => String(field).replace(/_/g, ' '))

    if (fields.length === 1) return fields[0]

    return `${fields.slice(0, -1).join(', ')} and ${fields[fields.length - 1]}`
}

const powerDetail = (properties: AuditProperties): string | null =>
    pick(properties, 'command')

export const AUDIT_EVENT_COPY: Record<AuditEvent, AuditEventCopy> = {
    // Authentication
    'auth.login.succeeded': { verb: 'signed in', detail: () => null },
    'auth.login.failed': {
        verb: 'failed to sign in',
        detail: (p) => pick(p, 'email'),
    },
    'auth.logout': { verb: 'signed out', detail: () => null },

    // Account and credentials
    'account.password.updated': {
        verb: 'changed their password',
        detail: () => null,
    },
    'account.two-factor.enabled': {
        verb: 'started setting up two-factor authentication',
        detail: () => null,
    },
    'account.two-factor.confirmed': {
        verb: 'enabled two-factor authentication',
        detail: () => null,
    },
    'account.two-factor.disabled': {
        verb: 'disabled two-factor authentication',
        detail: () => null,
    },
    'account.recovery-codes.regenerated': {
        verb: 'regenerated their recovery codes',
        detail: () => null,
    },
    'account.passkey.created': { verb: 'added a passkey' },
    'account.passkey.renamed': {
        verb: 'renamed a passkey',
        detail: (p) => {
            const from = pick(p, 'from')
            const to = pick(p, 'to')

            return from && to ? `${from} → ${to}` : to
        },
    },
    'account.passkey.deleted': { verb: 'removed a passkey' },
    'account.ssh-key.created': { verb: 'added an SSH key' },
    'account.ssh-key.deleted': { verb: 'removed an SSH key' },
    'account.api-key.created': { verb: 'created an API key' },
    'account.api-key.deleted': { verb: 'revoked an API key' },
    'account.session.revoked': {
        verb: 'signed out another session',
        detail: (p) => pick(p, 'ip'),
    },
    'account.oauth-connection.deleted': {
        verb: 'disconnected a linked account',
    },

    // Server actions
    'server.power.sent': { verb: 'sent a power command', detail: powerDetail },
    'server.reinstalled': { verb: 'reinstalled the server' },
    'server.installation.retried': {
        verb: 'retried the installation',
        detail: () => null,
    },
    'server.renamed': { verb: 'renamed the server' },
    'server.console.session-created': {
        verb: 'opened a console session',
        detail: (p) => pick(p, 'type'),
    },
    'server.console.display-enabled': {
        verb: 'enabled the display console',
        detail: () => null,
    },
    'server.console.serial-enabled': {
        verb: 'enabled the serial console',
        detail: () => null,
    },
    'server.backup.created': { verb: 'created a backup' },
    'server.backup.deleted': { verb: 'deleted a backup' },
    'server.backup.restored': { verb: 'restored a backup' },
    'server.firewall.options-updated': {
        verb: 'changed the firewall policy',
        detail: (p) => {
            const inbound = pick(p, 'inbound_policy')
            const outbound = pick(p, 'outbound_policy')

            return inbound && outbound ? `in ${inbound}, out ${outbound}` : null
        },
    },
    'server.firewall.rule-created': {
        verb: 'added a firewall rule',
        detail: (p) => pick(p, 'macro', 'destination_port', 'protocol'),
    },
    'server.firewall.rule-updated': {
        verb: 'changed a firewall rule',
        detail: (p) => pick(p, 'macro', 'destination_port', 'protocol'),
    },
    'server.firewall.rule-deleted': {
        verb: 'deleted a firewall rule',
        detail: () => null,
    },
    'server.firewall.rule-moved': {
        verb: 'reordered a firewall rule',
        detail: (p) => {
            const from = pick(p, 'from')
            const to = pick(p, 'to')

            return from && to ? `${from} → ${to}` : null
        },
    },
    'server.settings.auth-updated': {
        verb: 'changed the server credentials',
        detail: (p) => pick(p, 'type'),
    },
    'server.settings.boot-order-updated': {
        verb: 'changed the boot order',
        detail: () => null,
    },
    'server.settings.network-updated': {
        verb: 'changed the network settings',
        detail: () => null,
    },
    'server.media.mounted': { verb: 'mounted an ISO' },
    'server.media.unmounted': { verb: 'unmounted an ISO' },

    // Administrative actions on a server
    'admin.server.created': { verb: 'created the server' },
    'admin.server.updated': {
        verb: 'updated the server',
        detail: changedFields,
    },
    'admin.server.deleted': { verb: 'deleted the server' },
    'admin.server.power-sent': {
        verb: 'sent a power command',
        detail: powerDetail,
    },
    'admin.server.build-updated': {
        verb: 'changed the resource limits',
        detail: changedFields,
    },
    'admin.server.suspended': {
        verb: 'suspended the server',
        detail: () => null,
    },
    'admin.server.unsuspended': {
        verb: 'unsuspended the server',
        detail: () => null,
    },
    'admin.server.disk-created': {
        verb: 'added a disk',
        detail: (p) => pick(p, 'size'),
    },
    'admin.server.disk-updated': {
        verb: 'resized a disk',
        detail: (p) => {
            const from = pick(p, 'from')
            const to = pick(p, 'to')

            return from && to ? `${from} → ${to}` : null
        },
    },
    'admin.server.disk-deleted': { verb: 'removed a disk', detail: () => null },
    'admin.server.rehomed': {
        verb: 'moved the server between nodes',
        detail: (p) => {
            const from = pick(p, 'from')
            const to = pick(p, 'to')

            return from && to ? `${from} → ${to}` : null
        },
    },
    'admin.backup.deleted': { verb: 'deleted a backup' },

    // Infrastructure
    'admin.node.created': { verb: 'created a node' },
    'admin.node.updated': { verb: 'updated a node', detail: changedFields },
    'admin.node.deleted': { verb: 'deleted a node' },
    'admin.node.iso-created': { verb: 'added an ISO' },
    'admin.node.iso-updated': { verb: 'updated an ISO' },
    'admin.node.iso-deleted': { verb: 'deleted an ISO' },
    'admin.node.interface-created': { verb: 'added a network interface' },
    'admin.node.interface-updated': { verb: 'updated a network interface' },
    'admin.node.interface-deleted': { verb: 'deleted a network interface' },
    'admin.node.vlan-created': {
        verb: 'declared a VLAN',
        detail: (p) => pick(p, 'tag'),
    },
    'admin.node.vlan-updated': {
        verb: 'updated a VLAN',
        detail: (p) => pick(p, 'tag'),
    },
    'admin.node.vlan-deleted': {
        verb: 'removed a VLAN',
        detail: (p) => pick(p, 'tag'),
    },
    'admin.node.storage-created': { verb: 'added a storage' },
    'admin.node.storage-updated': { verb: 'updated a storage' },
    'admin.node.storage-deleted': { verb: 'removed a storage' },
    'admin.cluster.unflagged': {
        verb: 'cleared a cluster identity flag',
        detail: defaultDetail,
    },
    'admin.node.storage-backup-order-updated': {
        verb: 'reordered backup storage',
        detail: () => null,
    },
    'admin.location.created': { verb: 'created a location' },
    'admin.location.updated': {
        verb: 'updated a location',
        detail: changedFields,
    },
    'admin.location.deleted': { verb: 'deleted a location' },
    'admin.anchor.created': { verb: 'created an Anchor' },
    'admin.anchor.updated': { verb: 'updated an Anchor', detail: changedFields },
    'admin.anchor.deleted': { verb: 'deleted an Anchor' },
    'admin.anchor.enrollment-rotated': {
        verb: 'rotated an Anchor enrollment secret',
    },

    // IP address management
    'admin.address-block-group.created': { verb: 'created an address pool' },
    'admin.address-block-group.updated': {
        verb: 'updated an address pool',
        detail: changedFields,
    },
    'admin.address-block-group.deleted': { verb: 'deleted an address pool' },
    'admin.address-block-group.node-attached': {
        verb: 'attached a node to an address pool',
        detail: () => null,
    },
    'admin.address-block-group.node-detached': {
        verb: 'detached a node from an address pool',
    },
    'admin.address-block.created': { verb: 'created an address block' },
    'admin.address-block.updated': {
        verb: 'updated an address block',
        detail: changedFields,
    },
    'admin.address-block.deleted': { verb: 'deleted an address block' },
    'admin.address.generated': { verb: 'generated addresses' },
    'admin.address.updated': { verb: 'updated an address' },
    'admin.address.deleted': { verb: 'deleted an address' },
    'admin.address.reserved': { verb: 'reserved an address' },
    'admin.address.unreserved': { verb: 'released a reserved address' },

    // Users, tokens and panel settings
    'admin.user.created': { verb: 'created a user', detail: (p) => pick(p, 'email') },
    'admin.user.updated': { verb: 'updated a user', detail: changedFields },
    'admin.user.deleted': { verb: 'deleted a user' },
    'admin.user.sso-token-generated': {
        verb: 'generated a sign-in link for a user',
        detail: () => null,
    },
    'admin.token.created': { verb: 'created an application token' },
    'admin.token.updated': { verb: 'updated an application token' },
    'admin.token.deleted': { verb: 'revoked an application token' },
    'admin.settings.anchor-updated': {
        verb: 'changed the Anchor settings',
        detail: () => null,
    },
    'admin.settings.bandwidth-updated': {
        verb: 'changed the bandwidth settings',
        detail: (p) => pick(p, 'overage_action'),
    },

    // Presets and templates
    'admin.server-preset.created': { verb: 'created a server preset' },
    'admin.server-preset.updated': {
        verb: 'updated a server preset',
        detail: changedFields,
    },
    'admin.server-preset.deleted': { verb: 'deleted a server preset' },
    'admin.template-group.created': { verb: 'created a template group' },
    'admin.template-group.updated': {
        verb: 'updated a template group',
        detail: changedFields,
    },
    'admin.template-group.deleted': { verb: 'deleted a template group' },
    'admin.template.created': { verb: 'created a template' },
    'admin.template.updated': {
        verb: 'updated a template',
        detail: changedFields,
    },
    'admin.template.deleted': { verb: 'deleted a template' },
}

/** The sentence for one entry: what was done, and to what where that adds something. */
export const describeAuditEvent = (
    event: AuditEvent,
    properties: AuditProperties
): { verb: string; detail: string | null } => {
    const copy = AUDIT_EVENT_COPY[event]

    return {
        verb: copy.verb,
        detail: (copy.detail ?? defaultDetail)(properties),
    }
}
