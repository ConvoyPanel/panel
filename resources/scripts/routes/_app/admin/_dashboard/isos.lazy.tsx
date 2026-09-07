import { deleteIso, isoQueries, updateIso, useIsos } from '@/features/isos/api'
import CreateIsoModal from '@/features/isos/components/CreateIsoModal'
import { IconDisc, IconLockFilled, IconTrash } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import byteSize from 'byte-size'
import { useState } from 'react'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { Input } from '@/components/ui/Input'
import { PageToolbar } from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/isos')({
    component: IsoLibrary,
})

function IsoLibrary() {
    const [search, setSearch] = useState('')
    const { data, isLoading, isError, refetch } = useIsos(search || undefined)
    const queryClient = useQueryClient()
    const confirm = useConfirmationStore(state => state.confirm)

    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: isoQueries.all() })

    const isos = data?.items ?? []

    return (
        <>
            <Heading>ISOs</Heading>

            <PageToolbar actions={<CreateIsoModal />}>
                <Input
                    className={'max-w-xs'}
                    placeholder={'Search ISOs'}
                    value={search}
                    onChange={event => setSearch(event.target.value)}
                />
            </PageToolbar>

            {isError ? (
                <Card className={'py-6'}>
                    <CollectionErrorState onRetry={refetch} />
                </Card>
            ) : isLoading ? (
                <div className={'flex flex-col gap-2'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-16'} />
                    ))}
                </div>
            ) : isos.length === 0 ? (
                <Card className={'py-10'}>
                    <SimpleEmptyState
                        icon={IconDisc}
                        title={'No ISOs yet'}
                        description={
                            'Add one and it becomes available on every node — the first node someone mounts it on fetches it.'
                        }
                    />
                </Card>
            ) : (
                <Card>
                    {isos.map(iso => (
                        <div
                            key={iso.uuid}
                            className={
                                'flex min-h-[3.75rem] items-center gap-3 border-b px-4 py-2 last:border-b-0'
                            }
                        >
                            <div className={'min-w-0 grow'}>
                                <h3
                                    className={
                                        'flex items-center gap-1 font-semibold'
                                    }
                                >
                                    <span className={'truncate'}>
                                        {iso.name}
                                    </span>
                                    {iso.hidden && (
                                        <IconLockFilled
                                            className={
                                                '-mt-0.5 size-4 shrink-0'
                                            }
                                        />
                                    )}
                                </h3>
                                <p
                                    className={
                                        'text-muted-foreground truncate text-sm'
                                    }
                                >
                                    {iso.fileName}
                                    {iso.size ? ` · ${byteSize(iso.size)}` : ''}
                                    {' · '}
                                    {iso.isHosted
                                        ? 'hosted by Convoy'
                                        : 'linked'}
                                </p>
                            </div>

                            <Button
                                variant={'secondary'}
                                size={'sm'}
                                onClick={async () => {
                                    await updateIso(iso.uuid, {
                                        name: iso.name,
                                        hidden: !iso.hidden,
                                    })
                                    await refresh()
                                }}
                            >
                                {iso.hidden
                                    ? 'Show to customers'
                                    : 'Admin only'}
                            </Button>

                            <Button
                                variant={'ghost'}
                                size={'icon'}
                                aria-label={'Delete ISO'}
                                onClick={async () => {
                                    const ok = await confirm({
                                        title: `Delete ${iso.name}?`,
                                        description:
                                            'It disappears from the library. Copies nodes already fetched are left alone, and Proxmox treats them as cache.',
                                        confirmText: 'Delete ISO',
                                        confirmButton: {
                                            variant: 'destructive',
                                        },
                                    })

                                    if (!ok) return

                                    try {
                                        await deleteIso(iso.uuid)
                                        await refresh()
                                        toast.add({
                                            title: 'ISO deleted',
                                            type: 'success',
                                        })
                                    } catch {
                                        toast.add({
                                            title: 'Failed to delete the ISO',
                                            type: 'error',
                                        })
                                    }
                                }}
                            >
                                <IconTrash className={'size-4'} />
                            </Button>
                        </div>
                    ))}
                </Card>
            )}
        </>
    )
}
