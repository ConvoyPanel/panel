import { adminAuditQueries } from '@/features/audit/api.ts'
import AuditFeed from '@/features/audit/components/AuditFeed.tsx'
import usePagination from '@/hooks/use-pagination.ts'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { parseAsString, useQueryState } from 'nuqs'

import { PageToolbar } from '@/components/ui/PageToolbar'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/users/$userId/activity')({
    component: UserActivity,
})

/**
 * Two different questions, and the same account answers them differently: what this person did,
 * and what was done to their account (by an admin, or by the panel). Filtering on `actor_id` gets
 * the first; `subject_id` plus the morph type gets the second.
 */
const VIEWS = [
    { value: 'by', label: 'Actions by this user' },
    { value: 'to', label: 'Changes to this account' },
] as const

/** The morph value Eloquent stores for a User subject — no morph map is registered. */
const USER_SUBJECT_TYPE = 'App\\Models\\User'

function UserActivity() {
    const { userId } = Route.useParams()
    const numericUserId = Number(userId)
    const { page, setPage } = usePagination()
    const [view, setView] = useQueryState(
        'view',
        parseAsString.withDefault('by')
    )

    const { data, isLoading, isError, refetch } = useQuery(
        adminAuditQueries.list({
            page,
            filters:
                view === 'to'
                    ? {
                          subject_id: numericUserId,
                          subject_type: USER_SUBJECT_TYPE,
                      }
                    : { actor_id: numericUserId },
        })
    )

    return (
        <>
            <Heading>Activity</Heading>
            <PageToolbar>
                <Select
                    value={view}
                    onValueChange={value => {
                        setView(value)
                        // A narrower view can otherwise land the reader on a page that no longer
                        // exists, showing an empty list over a non-empty result.
                        setPage(1)
                    }}
                >
                    <SelectTrigger
                        className={'w-64'}
                        aria-label={'Filter activity'}
                    >
                        <SelectValue>
                            {(value: string) =>
                                VIEWS.find(option => option.value === value)
                                    ?.label ?? value
                            }
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        {VIEWS.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </PageToolbar>

            <AuditFeed
                data={data}
                isLoading={isLoading}
                isError={isError}
                onRetry={refetch}
                page={page}
                onPageChange={setPage}
                showSubject={view === 'by'}
                emptyTitle={'No activity yet'}
            />
        </>
    )
}
