import { useUser } from '@/features/auth/api.ts'
import { nodeQueries } from '@/features/nodes/api.ts'
import { serverQueries as adminServerQueries } from '@/features/servers/admin/api.ts'
import { serverQueries as clientServerQueries } from '@/features/servers/api.ts'
import { cn } from '@/utils'
import { useDebouncedValue } from '@mantine/hooks'
import {
    IconBoxMargin,
    IconChevronLeft,
    IconHome,
    IconKey,
    IconLock,
    IconMapPin,
    IconMapPins,
    IconPlus,
    IconSearch,
    IconServer,
} from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { useLocation, useRouter } from '@tanstack/react-router'
import { useMemo, useState } from 'react'

import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/Command'
import type {
    NavGroup,
    Route,
    SidebarNav,
} from '@/components/ui/Navigation/Navigation.types.ts'

interface CommandEntry {
    id: string
    icon: Route['icon']
    label: string
    path: string
    detail?: string
    keywords?: string[]
}

interface CommandEntryGroup {
    label: string
    items: CommandEntry[]
}

interface Props {
    nav: SidebarNav
    open: boolean
    onOpenChange: (open: boolean) => void
}

const clientGroups: NavGroup[] = [
    {
        label: 'Client',
        items: [
            {
                icon: IconServer,
                label: 'Servers',
                path: '/',
                activeOptions: { exact: true },
            },
            {
                icon: IconLock,
                label: 'Security',
                path: '/security',
            },
        ],
    },
]

const adminGroups: NavGroup[] = [
    {
        label: 'Admin',
        items: [
            {
                icon: IconHome,
                label: 'Dashboard',
                path: '/admin',
                activeOptions: { exact: true },
            },
            {
                icon: IconMapPin,
                label: 'Locations',
                path: '/admin/locations',
            },
            {
                icon: IconServer,
                label: 'Nodes',
                path: '/admin/nodes',
            },
            {
                icon: IconServer,
                label: 'Servers',
                path: '/admin/servers',
            },
            {
                icon: IconMapPins,
                label: 'IPAM',
                path: '/admin/ipam',
            },
            {
                icon: IconBoxMargin,
                label: 'Templates',
                path: '/admin/templates',
            },
            {
                icon: IconKey,
                label: 'API Tokens',
                path: '/admin/tokens',
            },
        ],
    },
]

const uniqueItems = (items: Route[]) => {
    const paths = new Set<string>()

    return items.filter(item => {
        if (paths.has(item.path)) {
            return false
        }

        paths.add(item.path)

        return true
    })
}

const uniqueEntries = (items: CommandEntry[]) => {
    const paths = new Set<string>()

    return items.filter(item => {
        if (paths.has(item.path)) {
            return false
        }

        paths.add(item.path)

        return true
    })
}

const routeToEntry = (item: Route, prefix: string): CommandEntry => ({
    id: `${prefix}:${item.path}`,
    icon: item.icon,
    label: item.label,
    path: item.path,
    detail: item.path,
})

const NavSearchDialog = ({ nav, open, onOpenChange }: Props) => {
    const [search, setSearch] = useState('')
    const [debouncedSearch] = useDebouncedValue(search.trim(), 200)
    const router = useRouter()
    const pathname = useLocation({ select: location => location.pathname })
    const { data: user } = useUser()
    const entitySearchEnabled = open && debouncedSearch.length > 0

    const clientServerParams = useMemo(
        () => ({
            perPage: 5,
            filter: debouncedSearch ? { name: debouncedSearch } : undefined,
        }),
        [debouncedSearch]
    )
    const adminServerParams = useMemo(
        () => ({
            page: 1,
            perPage: 5,
            filters: { '*': debouncedSearch },
            sorts: { name: 'asc' as const },
        }),
        [debouncedSearch]
    )
    const nodeParams = useMemo(
        () => ({
            page: 1,
            perPage: 5,
            filters: { '*': debouncedSearch },
            sorts: { display_name: 'asc' as const },
        }),
        [debouncedSearch]
    )

    const clientServers = useQuery({
        ...clientServerQueries.list(clientServerParams),
        enabled: entitySearchEnabled,
    })
    const adminServers = useQuery({
        ...adminServerQueries.list(adminServerParams),
        enabled: entitySearchEnabled && user?.rootAdmin === true,
    })
    const nodes = useQuery({
        ...nodeQueries.list(nodeParams),
        enabled: entitySearchEnabled && user?.rootAdmin === true,
    })

    const groups = useMemo<CommandEntryGroup[]>(() => {
        const currentItems = nav.groups.flatMap(group => group.items)
        const globalGroups = user?.rootAdmin
            ? [...clientGroups, ...adminGroups]
            : clientGroups
        const currentPaths = new Set(currentItems.map(item => item.path))
        const secondaryGroups = globalGroups
            .map(group => ({
                ...group,
                items: group.items.filter(item => !currentPaths.has(item.path)),
            }))
            .filter(group => group.items.length > 0)

        const routeGroups: CommandEntryGroup[] = [
            ...(currentItems.length > 0
                ? [
                      {
                          label: nav.context?.title ?? 'Current View',
                          items: uniqueItems(currentItems).map(item =>
                              routeToEntry(item, 'current')
                          ),
                      },
                  ]
                : []),
            ...(nav.back
                ? [
                      {
                          label: 'Context',
                          items: [
                              {
                                  id: `back:${nav.back.to}`,
                                  icon: IconChevronLeft,
                                  label: `Back to ${nav.back.label}`,
                                  path: nav.back.to,
                                  detail: 'Return to the parent view',
                                  keywords: ['back', 'parent'],
                              },
                          ],
                      },
                  ]
                : []),
            ...secondaryGroups.map((group, index) => ({
                label: group.label ?? 'Navigation',
                items: uniqueItems(group.items).map(item =>
                    routeToEntry(item, `global-${index}`)
                ),
            })),
        ]

        const actionItems: CommandEntry[] = [
            ...(user?.rootAdmin
                ? [
                      {
                          id: 'action:create-server',
                          icon: IconPlus,
                          label: 'Create server',
                          path: '/admin/servers/create',
                          detail: 'Admin action',
                          keywords: ['add', 'new', 'provision'],
                      },
                      {
                          id: 'action:create-node',
                          icon: IconPlus,
                          label: 'Create node',
                          path: '/admin/nodes/create',
                          detail: 'Admin action',
                          keywords: ['add', 'new', 'infrastructure'],
                      },
                      {
                          id: 'action:switch-admin',
                          icon: IconHome,
                          label: 'Switch to Admin Console',
                          path: '/admin',
                          detail: 'Workspace',
                          keywords: ['workspace', 'console'],
                      },
                  ]
                : []),
            {
                id: 'action:switch-client',
                icon: IconServer,
                label: 'Switch to Client Area',
                path: '/',
                detail: 'Workspace',
                keywords: ['workspace', 'servers'],
            },
            {
                id: 'action:security',
                icon: IconLock,
                label: 'Manage account security',
                path: '/security',
                detail: 'Account',
                keywords: ['account', 'passkeys', 'sessions', 'tokens'],
            },
        ].filter(item => item.path !== pathname)

        return [
            ...routeGroups,
            ...(actionItems.length > 0
                ? [{ label: 'Actions', items: uniqueEntries(actionItems) }]
                : []),
        ].filter(group => group.items.length > 0)
    }, [nav.back, nav.context?.title, nav.groups, pathname, user?.rootAdmin])

    const entityGroups = useMemo<CommandEntryGroup[]>(() => {
        if (!entitySearchEnabled) {
            return []
        }

        const result: CommandEntryGroup[] = []

        if (clientServers.data?.items.length) {
            result.push({
                label: 'My Servers',
                items: clientServers.data.items.map(server => ({
                    id: `client-server:${server.uuid}`,
                    icon: IconServer,
                    label: server.name,
                    path: `/servers/${server.uuid}`,
                    detail: server.hostname,
                    keywords: [
                        server.hostname,
                        server.uuid,
                        server.uuidShort,
                        server.status,
                    ],
                })),
            })
        }

        if (adminServers.data?.items.length) {
            result.push({
                label: 'Admin Servers',
                items: adminServers.data.items.map(server => ({
                    id: `admin-server:${server.id}`,
                    icon: IconServer,
                    label: server.name,
                    path: `/admin/servers/${server.id}`,
                    detail: server.hostname,
                    keywords: [
                        server.hostname,
                        server.uuid,
                        server.uuidShort,
                        server.status,
                    ],
                })),
            })
        }

        if (nodes.data?.items.length) {
            result.push({
                label: 'Nodes',
                items: nodes.data.items.map(node => ({
                    id: `node:${node.id}`,
                    icon: IconServer,
                    label: node.displayName,
                    path: `/admin/nodes/${node.id}`,
                    detail: node.fqdn,
                    keywords: [node.name, node.fqdn, String(node.id)],
                })),
            })
        }

        return result
    }, [
        adminServers.data?.items,
        clientServers.data?.items,
        entitySearchEnabled,
        nodes.data?.items,
    ])

    const isSearchingEntities =
        entitySearchEnabled &&
        (clientServers.isFetching ||
            (user?.rootAdmin === true &&
                (adminServers.isFetching || nodes.isFetching)))

    const selectItem = (item: CommandEntry) => {
        onOpenChange(false)
        setSearch('')
        void router.navigate({ to: item.path })
    }

    const handleOpenChange = (value: boolean) => {
        onOpenChange(value)

        if (!value) {
            setSearch('')
        }
    }

    return (
        <CommandDialog open={open} onOpenChange={handleOpenChange}>
            <CommandInput
                value={search}
                onValueChange={setSearch}
                placeholder='Search navigation, servers, nodes...'
            />
            <CommandList className='max-h-[420px]'>
                <CommandEmpty>No commands or entities found.</CommandEmpty>
                {[...entityGroups, ...groups].map((group, index) => (
                    <CommandGroup
                        key={group.label ?? `group-${index}`}
                        heading={group.label ?? 'Navigation'}
                    >
                        {group.items.map(item => (
                            <CommandItem
                                key={item.id}
                                value={`${item.label} ${item.path} ${item.detail ?? ''}`}
                                keywords={item.keywords}
                                onSelect={() => selectItem(item)}
                            >
                                <item.icon className='h-4 w-4' />
                                <span className='min-w-0 flex-1 truncate'>
                                    {item.label}
                                </span>
                                {item.detail ? (
                                    <span
                                        className={cn(
                                            'text-muted-foreground ml-auto truncate text-xs',
                                            item.detail.length > 24
                                                ? 'max-w-48'
                                                : null
                                        )}
                                    >
                                        {item.detail}
                                    </span>
                                ) : null}
                            </CommandItem>
                        ))}
                    </CommandGroup>
                ))}
                {isSearchingEntities ? (
                    <CommandGroup heading='Search'>
                        <CommandItem disabled value='searching entities'>
                            <IconSearch className='h-4 w-4' />
                            <span>Searching entities...</span>
                        </CommandItem>
                    </CommandGroup>
                ) : null}
            </CommandList>
        </CommandDialog>
    )
}

export default NavSearchDialog
