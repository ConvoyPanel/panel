import {
    provisionerQueries,
    useDeleteProvisionerMutation,
    useProvisioners,
} from '@/features/provisioners/api.ts'
import ProvisionerFormDialog, {
    type ProvisionerDialogTarget,
} from '@/features/provisioners/components/ProvisionerFormDialog.tsx'
import type { Provisioner } from '@/features/provisioners/types.ts'
import { IconPlus, IconTerminal2 } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { CardTable, type CardTableColumn } from '@/components/ui/CardTable'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import PageToolbar from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import Actions from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

const Provisioners = () => {
    const { data: provisioners, isLoading } = useProvisioners()
    const [target, setTarget] = useState<ProvisionerDialogTarget | null>(null)
    const confirm = useConfirmationStore(state => state.confirm)
    const { mutate: remove } = useDeleteProvisionerMutation()

    const confirmAndDelete = async (provisioner: Provisioner) => {
        const confirmed = await confirm({
            title: 'Delete provisioner',
            description:
                provisioner.imageCount > 0
                    ? `${provisioner.name} runs on ${countOf(provisioner.imageCount, 'image')}. Deleting it removes it from them. Servers already waiting on it still run it.`
                    : `Delete ${provisioner.name}? This cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            confirmButton: { variant: 'destructive' },
        })

        if (!confirmed) return

        remove(provisioner.uuid, {
            onSuccess: async () => {
                toast.add({ title: 'Provisioner deleted', type: 'success' })
                await queryClient.invalidateQueries({
                    queryKey: provisionerQueries.all(),
                })
            },
            onError: () =>
                toast.add({
                    title: 'Failed to delete provisioner',
                    type: 'error',
                }),
        })
    }

    const columns: CardTableColumn<Provisioner>[] = [
        {
            key: 'name',
            header: 'Provisioner',
            fill: true,
            cell: provisioner => (
                <div className={'flex min-w-0 flex-col'}>
                    <button
                        type={'button'}
                        className={
                            'truncate text-left font-medium hover:underline'
                        }
                        onClick={() => setTarget({ mode: 'edit', provisioner })}
                    >
                        {provisioner.name}
                    </button>
                    {provisioner.description && (
                        <p className={'text-muted-foreground truncate text-xs'}>
                            {provisioner.description}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: 'fields',
            header: 'Fields',
            className: 'whitespace-nowrap',
            cell: provisioner =>
                provisioner.fields.length === 0 ? (
                    <span className={'text-muted-foreground text-sm'}>
                        None
                    </span>
                ) : (
                    <span className={'font-mono text-sm'}>
                        {provisioner.fields.map(field => field.key).join(', ')}
                    </span>
                ),
        },
        {
            key: 'images',
            header: 'Images',
            align: 'right',
            cell: provisioner => provisioner.imageCount,
        },
        {
            key: 'actions',
            align: 'right',
            cell: provisioner => (
                <Actions>
                    <DropdownMenuItem
                        onClick={() => setTarget({ mode: 'edit', provisioner })}
                    >
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant={'destructive'}
                        onClick={() => confirmAndDelete(provisioner)}
                    >
                        Delete
                    </DropdownMenuItem>
                </Actions>
            ),
        },
    ]

    const addButton = (
        <Button onClick={() => setTarget({ mode: 'new' })}>
            <IconPlus className={'size-4'} /> Add provisioner
        </Button>
    )

    return (
        <>
            <Heading>Provisioners</Heading>
            <PageToolbar actions={addButton} />
            {isLoading ? (
                <Skeleton className={'h-64'} />
            ) : (
                <Card>
                    <CardContent className={'p-0'}>
                        <CardTable
                            caption={'Provisioners'}
                            rows={provisioners ?? []}
                            rowKey={provisioner => provisioner.uuid}
                            columns={columns}
                            empty={
                                <SimpleEmptyState
                                    icon={IconTerminal2}
                                    title={'No provisioners'}
                                    description={
                                        'Add a script, then attach it to an image.'
                                    }
                                    action={addButton}
                                />
                            }
                        />
                    </CardContent>
                </Card>
            )}

            <ProvisionerFormDialog
                target={target}
                close={() => setTarget(null)}
            />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/admin/_dashboard/provisioners')(
    {
        component: Provisioners,
    }
)
