import { useUser } from '@/features/auth/api.ts'
import useTitle from '@/hooks/use-title.ts'
import {
    IconBoxMargin,
    IconDatabase,
    IconDisc,
    IconHistory,
    IconHome,
    IconKey,
    IconMapPin,
    IconMapPins,
    IconServer,
    IconServer2,
    IconSettings,
    IconShieldLock,
    IconStack2,
    IconTransform,
    IconUsers,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import {
    NavGroup,
    type Route as NavRoute,
    SidebarNav,
} from '@/components/ui/Navigation/Navigation.types.ts'

type Permission = App.Enums.Admin.AdminPermission

/**
 * Every admin section, with the permission that opens it.
 *
 * Kept beside the item rather than resolved in the component, so the two cannot drift: a section
 * listed without a permission would be offered to a role that gets a 403 on it, which is worse
 * than not offering it at all.
 */
interface GatedItem extends NavRoute {
    permission: Permission
}

interface GatedGroup {
    label?: string
    items: GatedItem[]
}

const sections: GatedGroup[] = [
    {
        items: [
            {
                icon: IconHome,
                label: 'Dashboard',
                path: '/admin',
                activeOptions: { exact: true },
                permission: 'overview.read',
            },
        ],
    },
    {
        label: 'Infrastructure',
        items: [
            {
                icon: IconMapPin,
                label: 'Locations',
                path: '/admin/locations',
                permission: 'locations.read',
            },
            {
                icon: IconServer,
                label: 'Nodes',
                path: '/admin/nodes',
                permission: 'nodes.read',
            },
            {
                icon: IconTransform,
                label: 'Anchors',
                path: '/admin/anchors',
                permission: 'anchors.read',
            },
            {
                icon: IconServer,
                label: 'Servers',
                path: '/admin/servers',
                permission: 'servers.read',
            },
            {
                icon: IconServer2,
                label: 'Unmanaged Guests',
                path: '/admin/adoptable-guests',
                // Adoption is the only thing this screen does, so it is gated on
                // the permission that can act rather than the one that can look.
                permission: 'servers.manage',
            },
        ],
    },
    {
        label: 'Provisioning',
        items: [
            {
                icon: IconMapPins,
                label: 'IPAM',
                path: '/admin/ipam',
                permission: 'address-block-groups.read',
            },
            {
                icon: IconDatabase,
                label: 'Storage',
                path: '/admin/storage',
                permission: 'nodes.read',
            },
            {
                icon: IconBoxMargin,
                label: 'Images',
                path: '/admin/images',
                permission: 'image-groups.read',
            },
            {
                icon: IconDisc,
                label: 'ISOs',
                path: '/admin/isos',
                permission: 'isos.read',
            },
            {
                icon: IconStack2,
                label: 'Server Presets',
                path: '/admin/server-presets',
                permission: 'servers.read',
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                icon: IconUsers,
                label: 'Users',
                path: '/admin/users',
                permission: 'users.read',
            },
            {
                icon: IconShieldLock,
                label: 'Roles',
                path: '/admin/roles',
                permission: 'users.read',
            },
            {
                icon: IconKey,
                label: 'API Tokens',
                path: '/admin/tokens',
                permission: 'tokens.manage',
            },
            {
                icon: IconHistory,
                label: 'Audit Log',
                path: '/admin/audit-log',
                permission: 'audit-logs.read',
            },
            {
                icon: IconSettings,
                label: 'Settings',
                path: '/admin/settings',
                permission: 'settings.read',
            },
        ],
    },
]

/**
 * `manage` implies `read` on the same resource, matching the server-side rule, so a role granted
 * only `nodes.manage` still gets the Nodes item.
 */
const grants = (held: Permission[], required: Permission): boolean => {
    if (held.includes(required)) return true

    const [resource, action] = required.split('.')

    return action === 'read' && held.includes(`${resource}.manage` as Permission)
}

const navFor = (held: Permission[]): SidebarNav => ({
    key: 'admin',
    groups: sections
        .map(
            (group): NavGroup => ({
                label: group.label,
                items: group.items
                    .filter(item => grants(held, item.permission))
                    .map(({ permission: _permission, ...item }) => item),
            })
        )
        .filter(group => group.items.length > 0),
})

const AdminDashboardLayout = () => {
    useTitle()

    const { data: user } = useUser()

    return (
        <AppLayout routes={navFor(user?.adminPermissions ?? [])}>
            <Outlet />
        </AppLayout>
    )
}

export const Route = createFileRoute('/_app/admin/_dashboard')({
    component: AdminDashboardLayout,
    staticData: {
        title: 'Dashboard',
    },
})
