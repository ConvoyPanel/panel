import CreateStorageModal from '@/features/nodes/components/Storages/CreateStorageModal.tsx'
import DeleteStorageModal from '@/features/nodes/components/Storages/DeleteStorageModal.tsx'
import EditStorageModal from '@/features/nodes/components/Storages/EditStorageModal.tsx'
import LoadBalancerSidebar from '@/features/nodes/components/Storages/LoadBalancerSidebar.tsx'
import StorageList from '@/features/nodes/components/Storages/StorageList.tsx'
import { useStorages } from '@/features/nodes/storages/api.ts'
import { IconDatabase } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/storages')({
    component: NodeStorages,
})

function NodeStorages() {
    const { data: storages, isLoading, isError, refetch } = useStorages()

    return (
        <>
            <Heading>Storages</Heading>
            {/* Actions sit in their own row under the heading, the same
                toolbar-then-content rhythm every admin index uses. */}
            {Boolean(storages?.length) && (
                <div className={'flex flex-wrap items-center gap-2'}>
                    <div className={'ml-auto flex items-center gap-2'}>
                        <LoadBalancerSidebar />
                        <CreateStorageModal />
                    </div>
                </div>
            )}
            <DeleteStorageModal />
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
                <StorageList storages={storages ?? []} />
            )}
        </>
    )
}
