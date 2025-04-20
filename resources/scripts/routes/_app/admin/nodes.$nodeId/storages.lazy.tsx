import { IconDatabase } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr.ts'

import CreateStorageModal from '@/components/interfaces/Admin/Node/Storages/CreateStorageModal.tsx'
import StorageCard from '@/components/interfaces/Admin/Node/Storages/StorageCard.tsx'

import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/storages')({
    component: NodeStorages,
})

function NodeStorages() {
    const { data: storages, isLoading, mutate } = useStoragesSWR()

    return (
        <>
            <Heading>Storages</Heading>
            <CreateStorageModal mutate={mutate} />
            {isLoading ? (
                <div className={'flex flex-col gap-4'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-32'} />
                    ))}
                </div>
            ) : storages?.length === 0 ? (
                <SimpleEmptyState icon={IconDatabase} title={'Storages'} />
            ) : (
                <div className={'flex flex-col gap-4'}>
                    {storages!.map(storage => (
                        <StorageCard key={storage.id} storage={storage} />
                    ))}
                </div>
            )}
        </>
    )
}
