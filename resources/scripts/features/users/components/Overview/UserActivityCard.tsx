import { adminAuditQueries } from '@/features/audit/api.ts'
import AuditEntryRow from '@/features/audit/components/AuditEntryRow.tsx'
import { IconHistory } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'

import { buttonVariants } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    userId: number
}

const PREVIEW_COUNT = 5

/**
 * The account's own recent actions — what they did, not what was done to them. The Activity tab
 * carries both, and this is the half that answers "what has this person been up to".
 */
const UserActivityCard = ({ userId }: Props) => {
    const { data, isLoading, isError, refetch } = useQuery(
        adminAuditQueries.list({
            page: 1,
            perPage: PREVIEW_COUNT,
            filters: { actor_id: userId },
        })
    )

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent activity</CardTitle>
                <CardAction>
                    <Link
                        className={buttonVariants({
                            variant: 'outline',
                            size: 'sm',
                        })}
                        to={`/admin/users/${userId}/activity` as string}
                    >
                        View all
                    </Link>
                </CardAction>
            </CardHeader>
            <CardContent
                className={
                    isLoading || isError || data?.items.length === 0
                        ? 'grid min-h-[12rem] flex-1 place-items-center'
                        : 'flex flex-1 flex-col'
                }
            >
                {isError && !data ? (
                    <CollectionErrorState onRetry={refetch} />
                ) : isLoading || !data ? (
                    <Skeleton className={'h-40 w-full'} />
                ) : data.items.length === 0 ? (
                    <SimpleEmptyState
                        icon={IconHistory}
                        title={'Nothing recorded'}
                    />
                ) : (
                    <ItemGroup className={'gap-2'}>
                        {data.items.map(entry => (
                            <AuditEntryRow
                                key={entry.id}
                                entry={entry}
                                showSubject
                            />
                        ))}
                    </ItemGroup>
                )}
            </CardContent>
        </Card>
    )
}

export default UserActivityCard
