import CreateStorageModal from '@/features/nodes/components/Storages/CreateStorageModal.tsx'
import DeleteStorageModal from '@/features/nodes/components/Storages/DeleteStorageModal.tsx'
import EditStorageModal from '@/features/nodes/components/Storages/EditStorageModal.tsx'
import LoadBalancerSidebar from '@/features/nodes/components/Storages/LoadBalancerSidebar.tsx'
import ShowStorageModal from '@/features/nodes/components/Storages/ShowStorageModal.tsx'
import StorageCard from '@/features/nodes/components/Storages/StorageCard.tsx'
import { useStorages } from '@/features/nodes/storages/api.ts'
import { IconDatabase } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/storages')({
    component: NodeStorages,
})

function NodeStorages() {
    const { data: storages, isLoading, isError, refetch } = useStorages()

    return (
        <>
            <div
                className={'flex flex-wrap items-center justify-between gap-2'}
            >
                <Heading>Storages</Heading>
                {Boolean(storages?.length) && (
                    <div className={'flex gap-2'}>
                        <LoadBalancerSidebar />
                        <CreateStorageModal />
                    </div>
                )}
            </div>
            <DeleteStorageModal />
            <ShowStorageModal />
            <EditStorageModal />
            {isError && !storages ? (
                <Card className={'py-6'}>
                    <CollectionErrorState onRetry={refetch} />
                </Card>
            ) : isLoading ? (
                <div className={'flex flex-col gap-2'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-24'} />
                    ))}
                </div>
            ) : storages?.length === 0 ? (
                <Card className={'py-6'}>
                    <SimpleEmptyState
                        icon={IconDatabase}
                        title={'No storages'}
                        description={
                            'Add a storage to enable server deployments and resource management.'
                        }
                        action={<CreateStorageModal />}
                    />
                </Card>
            ) : (
                <ItemGroup className={'gap-3'}>
                    {storages?.map(storage => (
                        <StorageCard key={storage.id} storage={storage} />
                    ))}
                </ItemGroup>
            )}
        </>
    )
}
