import { z } from 'zod'

/** A named set of admin permissions. An account holds zero or one. */
export type AdminRole = App.Data.Admin.AdminRoleData

export type AdminPermission = App.Enums.Admin.AdminPermission

/**
 * Every admin permission, worded.
 *
 * Typed `Record<AdminPermission, string>` so adding a case to the PHP enum without wording it
 * here fails the typecheck rather than rendering a raw `address-block-groups.manage` at somebody.
 */
export const adminPermissionLabels: Record<AdminPermission, string> = {
    'overview.read': 'Dashboard and updates',
    'audit-logs.read': 'Audit log',
    'locations.read': 'View locations',
    'locations.manage': 'Manage locations',
    'nodes.read': 'View nodes and storage',
    'nodes.manage': 'Manage nodes and storage',
    'servers.read': 'View servers',
    'servers.manage': 'Manage servers',
    'servers.power': 'Power servers on and off',
    'address-block-groups.read': 'View IP address blocks',
    'address-block-groups.manage': 'Manage IP address blocks',
    'image-groups.read': 'View images',
    'image-groups.manage': 'Manage images',
    'isos.read': 'View ISOs',
    'isos.manage': 'Manage ISOs',
    'users.read': 'View accounts',
    'users.manage': 'Manage accounts and roles',
    'users.impersonate': 'Sign in as an account',
    'anchors.read': 'View anchors and relays',
    'anchors.manage': 'Manage anchors and relays',
    'tokens.manage': 'Manage panel API tokens',
    'settings.read': 'View settings',
    'settings.manage': 'Change settings',
}

/** Only where the label leaves a real question about what it opens up. */
export const adminPermissionDescriptions: Partial<
    Record<AdminPermission, string>
> = {
    'servers.power': 'Without being able to change or delete them.',
    'users.impersonate': 'Opens the panel as that customer.',
    'tokens.manage': 'A panel token can do everything this panel can.',
    'settings.manage': 'Includes mail, accounts and guest access.',
}

export interface AdminPermissionGroup {
    label: string
    permissions: AdminPermission[]
}

export const adminPermissionGroups: AdminPermissionGroup[] = [
    {
        label: 'Overview',
        permissions: ['overview.read', 'audit-logs.read'],
    },
    {
        label: 'Servers',
        permissions: ['servers.read', 'servers.power', 'servers.manage'],
    },
    {
        label: 'Infrastructure',
        permissions: [
            'locations.read',
            'locations.manage',
            'nodes.read',
            'nodes.manage',
            'anchors.read',
            'anchors.manage',
            'address-block-groups.read',
            'address-block-groups.manage',
        ],
    },
    {
        label: 'Provisioning',
        permissions: [
            'image-groups.read',
            'image-groups.manage',
            'isos.read',
            'isos.manage',
        ],
    },
    {
        label: 'Accounts',
        permissions: ['users.read', 'users.manage', 'users.impersonate'],
    },
    {
        label: 'Panel',
        permissions: ['settings.read', 'settings.manage', 'tokens.manage'],
    },
]

export const adminRoleSchema = z.object({
    name: z.string().min(1, 'A name is required').max(191),
    description: z.string().max(191).nullable(),
    // Membership is the backend's to police; this validates the shape.
    permissions: z.array(
        z.custom<AdminPermission>(value => typeof value === 'string')
    ),
})

export type AdminRoleInput = z.infer<typeof adminRoleSchema>
