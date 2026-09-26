import { z } from 'zod'

/** One person's access to a server, as the owner sees it. */
export type Subuser = App.Data.Server.ServerSubuserData

export type ServerPermission = App.Enums.Server.ServerPermission

/**
 * Every permission, grouped the way the server's own sidebar is.
 *
 * The record is typed `Record<ServerPermission, string>` so adding a case to the PHP enum without
 * wording it here fails the typecheck rather than rendering a raw `settings.boot-order` at
 * somebody. Same contract the audit-event copy map has.
 */
export const permissionLabels: Record<ServerPermission, string> = {
    'power.start': 'Start',
    'power.stop': 'Shut down',
    'power.restart': 'Restart',
    'power.kill': 'Force off',
    'console.session': 'Open a console',
    'console.configure': 'Add a console device',
    'statistics.read': 'Graphs',
    'activity.read': 'Activity log',
    'backup.read': 'List backups',
    'backup.create': 'Take backups',
    'backup.restore': 'Restore backups',
    'backup.delete': 'Delete backups',
    'firewall.read': 'View firewall rules',
    'firewall.update': 'Change firewall rules',
    'settings.rename': 'Rename',
    'settings.boot-order': 'Boot order',
    'settings.media': 'Mount ISOs',
    'settings.network': 'Nameservers',
    'settings.auth': 'Root password and SSH keys',
    'settings.reinstall': 'Reinstall',
}

/** What each permission actually lets them do, where the label alone leaves a real question. */
export const permissionDescriptions: Partial<Record<ServerPermission, string>> =
    {
        'power.kill': 'Cuts power without letting the OS shut down.',
        'backup.restore': 'Overwrites the disk with an earlier backup.',
        'settings.auth': 'Sets the credentials for signing in to the OS.',
        'settings.reinstall': 'Erases the disk and installs a fresh OS.',
    }

export interface PermissionGroup {
    label: string
    permissions: ServerPermission[]
}

export const permissionGroups: PermissionGroup[] = [
    {
        label: 'Power',
        permissions: [
            'power.start',
            'power.stop',
            'power.restart',
            'power.kill',
        ],
    },
    {
        label: 'Console',
        permissions: ['console.session', 'console.configure'],
    },
    {
        label: 'Monitoring',
        permissions: ['statistics.read', 'activity.read'],
    },
    {
        label: 'Backups',
        permissions: [
            'backup.read',
            'backup.create',
            'backup.restore',
            'backup.delete',
        ],
    },
    {
        label: 'Firewall',
        permissions: ['firewall.read', 'firewall.update'],
    },
    {
        label: 'Settings',
        permissions: [
            'settings.rename',
            'settings.boot-order',
            'settings.media',
            'settings.network',
            'settings.auth',
            'settings.reinstall',
        ],
    },
]

/**
 * The catalog is the backend's, so this validates the shape rather than the membership: an
 * unknown value is refused there, with the message that belongs to it.
 */
const permissionList = z.array(
    z.custom<ServerPermission>(value => typeof value === 'string')
)

export const shareServerSchema = z.object({
    email: z.email('Enter a valid email address').max(191),
    permissions: permissionList,
})

export const subuserPermissionsSchema = z.object({
    permissions: permissionList,
})

export type ShareServerInput = z.infer<typeof shareServerSchema>
export type SubuserPermissionsInput = z.infer<typeof subuserPermissionsSchema>
