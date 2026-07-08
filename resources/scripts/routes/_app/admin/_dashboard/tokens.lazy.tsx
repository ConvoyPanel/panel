import useDataTable from '@/hooks/use-data-table.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { formatDistanceToNow } from 'date-fns'
import { toast } from 'sonner'

import { deleteToken, tokenQueries, useTokens } from '@/features/tokens/api.ts'
import {
    summarizeAbilities,
    type ApiKey,
    type PaginatedApiKeys,
} from '@/features/tokens/types.ts'

import CreateTokenModal from '@/features/tokens/components/CreateTokenModal.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Badge } from '@/components/ui/Badge.tsx'
import { DataTable } from '@/components/ui/DataTable'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/tokens')({
    component: TokensIndex,
})

function TokensIndex() {
    const confirm = useConfirmationStore(state => state.confirm)
    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData } = useTokens(queryParams)
    const mutate = useQueryMutator<PaginatedApiKeys>(
        tokenQueries.list(queryParams).queryKey
    )

    const { mutate: revoke } = useMutation({
        mutationFn: (token: ApiKey) => deleteToken(token.id),
        onSuccess: (_, token) => {
            mutate(data =>
                data
                    ? {
                          ...data,
                          items: data.items.filter(t => t.id !== token.id),
                      }
                    : data
            )
            toast.success('API token revoked')
        },
        onError: () => toast.error('Failed to revoke token'),
    })

    const handleDelete = async (token: ApiKey) => {
        const confirmed = await confirm({
            title: 'Revoke API token',
            description: `Any integration using “${token.name}” will immediately lose access. This cannot be undone.`,
        })
        if (!confirmed) return

        revoke(token)
    }

    const columns: ColumnDef<ApiKey>[] = [
        {
            header: 'Name',
            accessorKey: 'name',
            enableHiding: false,
            meta: { skeletonWidth: '8rem' },
        },
        {
            header: 'Abilities',
            id: 'abilities',
            meta: { skeletonWidth: '6rem' },
            cell: ({ row }) => (
                <Badge variant={'secondary'}>
                    {summarizeAbilities(row.original.abilities)}
                </Badge>
            ),
        },
        {
            header: 'Created by',
            id: 'createdBy',
            meta: { skeletonWidth: '8rem' },
            cell: ({ row }) =>
                row.original.createdBy?.email ?? (
                    <span className={'text-muted-foreground'}>—</span>
                ),
        },
        {
            header: 'Last used',
            accessorKey: 'lastUsedAt',
            meta: { skeletonWidth: '6rem' },
            cell: ({ cell }) => {
                const value = cell.getValue<string | null>()
                return value ? (
                    formatDistanceToNow(new Date(value), { addSuffix: true })
                ) : (
                    <span className={'text-muted-foreground'}>Never</span>
                )
            },
        },
        actionsColumn<ApiKey>(({ row }) => (
            <DropdownMenuItem onClick={() => handleDelete(row.original)}>
                Revoke
            </DropdownMenuItem>
        )),
    ]

    return (
        <>
            <Heading>API Tokens</Heading>
            <DataTable
                data={data}
                columns={columns}
                paginated
                toolbar
                isPlaceholderData={isPlaceholderData}
                rightActions={<CreateTokenModal mutate={mutate} />}
                {...tableProps}
            />
        </>
    )
}
