import { cn } from '@/utils'
import {
    IconAlertTriangle,
    IconCheck,
    IconDatabaseExclamation,
    IconDisc,
    IconNetwork,
    IconPlayerPause,
    IconServerBolt,
    IconUserPlus,
} from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
    OverflowItemGroup,
} from '@/components/ui/Item'

import { bytes, num } from './overview-helpers'

type OverviewData = App.Data.Admin.Overview.OverviewData

/** Cap so the card never grows or shifts the page; overflow opens the sheet. */
const MAX_VISIBLE = 4

type ItemTone = 'alert' | 'neutral' | 'setup'

interface AttentionItem {
    id: string
    tone: ItemTone
    icon: TablerIcon
    title: string
    description: string
    action: ReactNode
}

const iconWrapClass: Record<ItemTone, string> = {
    alert: 'bg-destructive/10 text-destructive',
    neutral: 'bg-muted text-foreground',
    setup: 'bg-primary/10 text-primary',
}

const ActionLink = ({ to, children }: { to: string; children: ReactNode }) => (
    <Link
        to={to}
        className='text-primary ml-auto shrink-0 text-sm font-semibold hover:underline'
    >
        {children}
    </Link>
)

const AttentionRow = ({ item }: { item: AttentionItem }) => {
    const Icon = item.icon
    return (
        <Item variant='muted' size='sm'>
            <ItemMedia>
                <span
                    className={cn(
                        'grid size-8 shrink-0 place-items-center rounded-lg',
                        iconWrapClass[item.tone]
                    )}
                >
                    <Icon className='size-4' />
                </span>
            </ItemMedia>
            <ItemContent className='min-w-0'>
                {/* ItemTitle is a `w-fit` flex row, so the ellipsis has to live
                    on a child that can actually shrink. */}
                <ItemTitle className='w-full min-w-0'>
                    <span className='truncate'>{item.title}</span>
                </ItemTitle>
                <ItemDescription className='block truncate text-nowrap text-xs'>
                    {item.description}
                </ItemDescription>
            </ItemContent>
            <ItemActions className='shrink-0'>{item.action}</ItemActions>
        </Item>
    )
}

/** Real problems worth surfacing, most-severe first. Empty when all is well. */
const deriveAttention = (data: OverviewData): AttentionItem[] => {
    const { summary, servers, backups, nodes } = data
    const items: AttentionItem[] = []

    if (summary.failedServers > 0) {
        items.push({
            id: 'failed-servers',
            tone: 'alert',
            icon: IconAlertTriangle,
            title: `${num(summary.failedServers)} server${summary.failedServers === 1 ? '' : 's'} failed to install`,
            description: 'Review provisioning logs on the affected nodes.',
            action: <ActionLink to='/admin/servers'>Review</ActionLink>,
        })
    }

    if (backups.failed > 0) {
        items.push({
            id: 'failed-backups',
            tone: 'alert',
            icon: IconDatabaseExclamation,
            title: `${num(backups.failed)} backup${backups.failed === 1 ? '' : 's'} failed`,
            description: 'Backups that did not complete successfully.',
            action: <ActionLink to='/admin/servers'>View</ActionLink>,
        })
    }

    nodes
        .filter(node => node.memory.percent >= 90)
        .forEach(node => {
            items.push({
                id: `node-capacity-${node.id}`,
                tone: 'neutral',
                icon: IconServerBolt,
                title: `${node.displayName} near memory capacity`,
                description: `Allocated ${node.memory.percent}% of ${bytes(node.memory.total)}.`,
                action: <ActionLink to='/admin/nodes'>View</ActionLink>,
            })
        })

    if (servers.suspended > 0) {
        items.push({
            id: 'suspended-servers',
            tone: 'neutral',
            icon: IconPlayerPause,
            title: `${num(servers.suspended)} server${servers.suspended === 1 ? '' : 's'} suspended`,
            description: 'Suspended servers are not running.',
            action: <ActionLink to='/admin/servers'>View</ActionLink>,
        })
    }

    return items
}

/** Onboarding checklist shown on a fresh install instead of a wall of zeros. */
const deriveSetup = (data: OverviewData): AttentionItem[] => [
    {
        id: 'setup-node',
        tone: 'setup',
        icon: IconServerBolt,
        title: 'Add another node',
        description: `You have ${num(data.summary.nodes)}. More nodes, more capacity.`,
        action: <ActionLink to='/admin/nodes'>Add node</ActionLink>,
    },
    {
        id: 'setup-ip',
        tone: 'setup',
        icon: IconNetwork,
        title: 'Create an IP pool',
        description: 'Servers need addresses to come online.',
        action: <ActionLink to='/admin/ipam'>New pool</ActionLink>,
    },
    {
        id: 'setup-iso',
        tone: 'setup',
        icon: IconDisc,
        title: 'Upload an ISO',
        description: 'Give users something to install from.',
        action: <ActionLink to='/admin/nodes'>Add ISO</ActionLink>,
    },
    {
        id: 'setup-team',
        tone: 'setup',
        icon: IconUserPlus,
        title: 'Invite your team',
        description: 'Add admins and give them access.',
        action: <ActionLink to='/admin/servers'>Invite</ActionLink>,
    },
]

interface Props {
    data: OverviewData
    /** Fresh install → onboarding checklist instead of alerts. */
    isFresh: boolean
}

const NeedsAttentionCard = ({ data, isFresh }: Props) => {
    const items = isFresh ? deriveSetup(data) : deriveAttention(data)
    const title = isFresh ? 'Finish setup' : 'Needs attention'

    return (
        <Card className='flex flex-col'>
            <CardHeader className='p-5 pb-2'>
                <CardTitle className='text-base'>{title}</CardTitle>
            </CardHeader>
            <CardContent className='flex flex-1 flex-col p-5 pt-0'>
                {items.length === 0 ? (
                    <SimpleEmptyState
                        className='p-0'
                        icon={IconCheck}
                        title='All clear'
                        description='No failed servers, backups, or capacity warnings.'
                    />
                ) : (
                    <OverflowItemGroup
                        title={title}
                        max={MAX_VISIBLE}
                        rows={items.map(item => (
                            <AttentionRow key={item.id} item={item} />
                        ))}
                    />
                )}
            </CardContent>
        </Card>
    )
}

export default NeedsAttentionCard
