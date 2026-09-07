import { cn } from '@/utils'
import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { OverflowItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    title: string
    description: string
    icon: TablerIcon
    isLoading: boolean
    isError: boolean
    onRetry: () => void
    emptyTitle: string
    emptyDescription: string
    /** One node per credential; the card handles overflow into a sheet past the third. */
    rows: ReactNode[] | undefined
    action?: ReactNode
}

/**
 * The shell every credential list on this page shares: header, the four states a fetched list can
 * be in, and the overflow behaviour.
 *
 * Same shape as the account's own `/security` cards (KeychainCard and friends) — an operator
 * looking at someone else's keys should not be looking at a different design from the one they use
 * on their own account. `flex-1` plus the `min-h` on the empty state is what stops a short card in
 * a stretched grid row from floating its content against the header.
 */
const CredentialCard = ({
    title,
    description,
    icon,
    isLoading,
    isError,
    onRetry,
    emptyTitle,
    emptyDescription,
    rows,
    action,
}: Props) => {
    const isEmpty = rows?.length === 0

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
                {action && <CardAction>{action}</CardAction>}
            </CardHeader>
            <CardContent
                className={cn(
                    'flex-1',
                    isLoading || isError || isEmpty
                        ? 'grid min-h-[12rem] place-items-center'
                        : 'flex flex-col'
                )}
            >
                {isError && !rows ? (
                    <CollectionErrorState onRetry={onRetry} />
                ) : isLoading || !rows ? (
                    <Skeleton className={'h-40 w-full'} />
                ) : isEmpty ? (
                    <SimpleEmptyState
                        icon={icon}
                        title={emptyTitle}
                        description={emptyDescription}
                    />
                ) : (
                    <OverflowItemGroup title={title} rows={rows} />
                )}
            </CardContent>
        </Card>
    )
}

export default CredentialCard
