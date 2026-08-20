import { adminAuditQueries } from '@/features/audit/api.ts'
import AuditFeed from '@/features/audit/components/AuditFeed.tsx'
import usePagination from '@/hooks/use-pagination.ts'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { parseAsString, useQueryState } from 'nuqs'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import { Heading } from '@/components/ui/Typography'

/**
 * Prefix filters over the dotted event key. The backend matches `event LIKE '<area>%'`, so one
 * entry here covers every event in that area without listing them.
 */
const AREAS = [
    { value: 'all', label: 'All activity' },
    { value: 'auth', label: 'Authentication' },
    { value: 'account', label: 'Accounts' },
    { value: 'server', label: 'Server actions' },
    { value: 'admin.server', label: 'Admin: servers' },
    { value: 'admin.node', label: 'Admin: nodes' },
    { value: 'admin.user', label: 'Admin: users' },
    { value: 'admin.token', label: 'Admin: tokens' },
    { value: 'admin.address', label: 'Admin: addressing' },
    { value: 'admin.template', label: 'Admin: templates' },
    { value: 'admin.settings', label: 'Admin: settings' },
] as const

function AdminAuditLog() {
    const { page, setPage } = usePagination()
    const [area, setArea] = useQueryState(
        'area',
        parseAsString.withDefault('all')
    )

    const { data, isLoading, isError, refetch } = useQuery(
        adminAuditQueries.list({
            page,
            // `filters`, not `filter` — withQueryBuilderParams reads the plural key, and a spread
            // of an object literal skips TypeScript's excess-property check, so the wrong one just
            // vanishes and the list comes back unfiltered.
            ...(area === 'all' ? {} : { filters: { area } }),
        })
    )

    return (
        <>
            <div className={'flex flex-wrap items-center justify-between gap-2'}>
                <Heading>Audit Log</Heading>
                <Select
                    value={area}
                    onValueChange={value => {
                        setArea(value)
                        // Otherwise a narrower filter can land the reader on a page that no
                        // longer exists, showing an empty list over a non-empty result.
                        setPage(1)
                    }}
                >
                    <SelectTrigger className={'w-56'} aria-label={'Filter activity'}>
                        {/* Base UI renders the raw value unless given a formatter, which would
                            show "admin.node" where the reader expects "Admin: nodes". */}
                        <SelectValue>
                            {(value: string) =>
                                AREAS.find(option => option.value === value)
                                    ?.label ?? 'All activity'
                            }
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        {AREAS.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <p className={'text-sm text-muted-foreground'}>
                Everything recorded across the panel. Security events are kept
                indefinitely; operational events age out.
            </p>
            <AuditFeed
                data={data}
                isLoading={isLoading}
                isError={isError}
                onRetry={refetch}
                page={page}
                onPageChange={setPage}
                showSubject
                emptyTitle={'Nothing recorded yet'}
                emptyDescription={
                    'Panel activity will appear here as it happens.'
                }
            />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/admin/_dashboard/audit-log')({
    component: AdminAuditLog,
})
