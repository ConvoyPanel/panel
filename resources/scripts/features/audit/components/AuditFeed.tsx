import type { AuditEntry, PaginatedAuditEntries } from '@/features/audit/api.ts'
import AuditEntryRow from '@/features/audit/components/AuditEntryRow.tsx'
import { cn } from '@/utils'
import { IconHistory } from '@tabler/icons-react'

import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    data: PaginatedAuditEntries | undefined
    isLoading: boolean
    isError: boolean
    onRetry: () => void
    page: number
    onPageChange: (page: number) => void
    showSubject?: boolean
    emptyTitle?: string
    emptyDescription?: string
}

/**
 * The list half of an activity view, shared by the server tab and the global admin log. Everything
 * that differs between the two — which query feeds it, whether the subject is worth naming — is a
 * prop, so the two views cannot drift apart visually.
 */
const AuditFeed = ({
    data,
    isLoading,
    isError,
    onRetry,
    page,
    onPageChange,
    showSubject = false,
    emptyTitle = 'No activity yet',
    emptyDescription = 'Actions taken here will be listed as they happen.',
}: Props) => {
    // `isError && !data` rather than `isError`: a background refetch that fails should not throw
    // away a page the reader is already looking at.
    if (isError && !data) {
        return <CollectionErrorState onRetry={onRetry} />
    }

    if (isLoading && !data) {
        return (
            <div className={'flex flex-col gap-2'}>
                {Array.from({ length: 5 }).map((_, index) => (
                    <Skeleton key={index} className={'h-14 w-full'} />
                ))}
            </div>
        )
    }

    if (!data || data.items.length === 0) {
        return (
            <SimpleEmptyState
                icon={IconHistory}
                title={emptyTitle}
                description={emptyDescription}
            />
        )
    }

    return (
        <LengthAwarePaginator
            page={page}
            data={data}
            onPageChange={onPageChange}
        >
            {({ items }: { items: AuditEntry[] }) => (
                <div
                    className={cn(
                        'flex flex-col gap-2',
                        // Dimmed rather than replaced while the next page loads, so the reader
                        // keeps their place instead of watching the list disappear.
                        isLoading && 'opacity-60 transition-opacity'
                    )}
                >
                    {items.map(entry => (
                        <AuditEntryRow
                            key={entry.id}
                            entry={entry}
                            showSubject={showSubject}
                        />
                    ))}
                </div>
            )}
        </LengthAwarePaginator>
    )
}

export default AuditFeed
