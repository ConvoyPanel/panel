import { IconDatabase } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { useStorages } from '@/features/nodes/storages/api.ts'

import CreateStorageModal from '@/features/nodes/components/Storages/CreateStorageModal.tsx'
import DeleteStorageModal from '@/features/nodes/components/Storages/DeleteStorageModal.tsx'
import EditStorageModal from '@/features/nodes/components/Storages/EditStorageModal.tsx'
import LoadBalancerSidebar from '@/features/nodes/components/Storages/LoadBalancerSidebar.tsx'
import ShowStorageModal from '@/features/nodes/components/Storages/ShowStorageModal.tsx'
import StorageCard from '@/features/nodes/components/Storages/StorageCard.tsx'

import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/storages')({
    component: NodeStorages,
})

function NodeStorages() {
    const { data: storages, isLoading } = useStorages()

    return (
        <>
            <Heading>Storages</Heading>
            <div className={'flex justify-end gap-2'}>
                <LoadBalancerSidebar />
                <CreateStorageModal />
            </div>
            <DeleteStorageModal />
            <ShowStorageModal />
            <EditStorageModal />
            {isLoading ? (
                <div className={'flex flex-col gap-2'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-24'} />
                    ))}
                </div>
            ) : storages?.length === 0 ? (
                <Card>
                    <CardHeader className={'pb-0'} />
                    <CardContent>
                        <SimpleEmptyState
                            icon={IconDatabase}
                            title={'Storages'}
                            description={
                                'No storages have been created for this node yet. Add a storage to enable server deployments and resource management.'
                            }
                        />
                    </CardContent>
                </Card>
            ) : (
                <div className={'flex flex-col gap-2'}>
                    {storages!.map(storage => (
                        <StorageCard key={storage.id} storage={storage} />
                    ))}
                </div>
            )}
        </>
    )
}