import anchorStatus, { toneDotClass } from '@/features/anchors/status.ts'
import type { Anchor } from '@/features/anchors/types.ts'
import { cn } from '@/utils'
import { IconPlugConnected, IconServer } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'
import { ReactNode } from 'react'

import { buttonVariants } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
    OverflowItemGroup,
} from '@/components/ui/Item'
import { StatLabel } from '@/components/ui/Typography'

interface Props {
    anchor: Anchor
    /** Every anchor in the fleet; a relay's dependents are found among them. */
    fleet: Anchor[]
}

const row = ({
    key,
    to,
    params,
    title,
    subtitle,
    trailing,
}: {
    key: string | number
    to: string
    params: Record<string, string>
    title: string
    subtitle: string
    trailing: ReactNode
}) => (
    <Item key={key} variant='muted' size='sm'>
        <ItemContent className='min-w-0 overflow-x-hidden'>
            <ItemTitle className='w-full min-w-0'>
                <Link
                    className={cn(
                        buttonVariants({ variant: 'link' }),
                        'h-auto max-w-full min-w-0 shrink p-0'
                    )}
                    to={to}
                    params={params}
                >
                    <span className='truncate'>{title}</span>
                </Link>
            </ItemTitle>
            {/* `block`/`text-nowrap` beat ItemDescription's line-clamp-2 and
                text-balance, which otherwise silently defeat `truncate`. */}
            <ItemDescription className='block truncate text-nowrap'>
                {subtitle}
            </ItemDescription>
        </ItemContent>
        <ItemActions>{trailing}</ItemActions>
    </Item>
)

/**
 * What goes dark if this anchor does. The roster only has room for the count,
 * and a count is the wrong unit for that question -- "4 nodes" doesn't say
 * whether the one carrying 18 servers is among them.
 *
 * A relay carries no nodes of its own, so the same question resolves one level
 * out: the agents routed through it.
 *
 * The list is an `OverflowItemGroup` rather than every row inline. A detail
 * page is read at a glance and a fleet is not bounded, so an inline list makes
 * the page's height a function of someone's node count; the overflow sheet is
 * the primitive this codebase already uses for exactly that (SSHKeysCard, the
 * admin needs-attention card).
 */
const AnchorDependentsCard = ({ anchor, fleet }: Props) => {
    const isRelay = anchor.mode === 'relay'
    const agents = isRelay
        ? fleet.filter(item => item.relayId === anchor.id)
        : []
    const nodes = anchor.nodes ?? []
    const count = isRelay ? agents.length : nodes.length

    const rows = isRelay
        ? agents.map(agent => {
              const status = anchorStatus(agent)

              return row({
                  key: agent.id,
                  to: '/admin/anchors/$anchorId',
                  params: { anchorId: String(agent.id) },
                  title: agent.name,
                  subtitle: agent.publicUrl,
                  trailing: (
                      <span className='flex items-center gap-2 text-sm whitespace-nowrap'>
                          <span
                              className={cn(
                                  'size-2 shrink-0 rounded-full',
                                  toneDotClass[status.tone]
                              )}
                              aria-hidden
                          />
                          {status.label}
                      </span>
                  ),
              })
          })
        : nodes.map(node =>
              row({
                  key: node.id,
                  to: '/admin/nodes/$nodeId',
                  params: { nodeId: String(node.id) },
                  title: node.displayName,
                  subtitle: node.fqdn,
                  trailing: (
                      <span className='text-sm whitespace-nowrap tabular-nums'>
                          {node.serversCount}{' '}
                          <span className='text-muted-foreground'>
                              server{node.serversCount === 1 ? '' : 's'}
                          </span>
                      </span>
                  ),
              })
          )

    const title = isRelay
        ? 'Agents routed through this relay'
        : 'Attached nodes'

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>
                    {isRelay
                        ? 'Their traffic reaches the panel by way of this relay.'
                        : 'These lose console access while this anchor is away.'}
                </CardDescription>
                {count > 0 && (
                    <CardAction>
                        <StatLabel as='span' className='text-xs'>
                            {count} {isRelay ? 'agent' : 'node'}
                            {count === 1 ? '' : 's'}
                        </StatLabel>
                    </CardAction>
                )}
            </CardHeader>
            {/* flex-1 so the card can be the short one in a stretched row
                without its empty state riding up against the header. */}
            <CardContent className='flex-1'>
                {count === 0 ? (
                    <SimpleEmptyState
                        className='grid min-h-[8rem] place-items-center p-0'
                        icon={isRelay ? IconPlugConnected : IconServer}
                        title={
                            isRelay
                                ? 'Nothing routes through it'
                                : 'No nodes attached'
                        }
                        description={
                            isRelay
                                ? 'Point an agent at this relay from the agent’s own settings.'
                                : 'Attach this anchor to a node from the node’s settings.'
                        }
                    />
                ) : (
                    <OverflowItemGroup max={6} title={title} rows={rows} />
                )}
            </CardContent>
        </Card>
    )
}

export default AnchorDependentsCard
