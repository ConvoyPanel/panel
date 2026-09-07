import { useImageGroups } from '@/features/images/api.ts'
import CreateImageGroupModal from '@/features/images/components/CreateImageGroupModal.tsx'
import DeleteImageGroupModal from '@/features/images/components/DeleteImageGroupModal.tsx'
import EditImageGroupModal from '@/features/images/components/EditImageGroupModal.tsx'
import ImageGroupCard from '@/features/images/components/ImageGroupCard.tsx'
import ImageGroupSidebar from '@/features/images/components/ImageGroupSidebar.tsx'
import { IconDisc } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import { PageToolbar } from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/images')({
    component: ImagesIndex,
})

function ImagesIndex() {
    const { data: groups, isLoading, isError, refetch } = useImageGroups({})

    return (
        <>
            <Heading>Images</Heading>
            {Boolean(groups?.length) && (
                <PageToolbar actions={<CreateImageGroupModal />} />
            )}

            <EditImageGroupModal />
            <DeleteImageGroupModal />
            <ImageGroupSidebar />

            {isError && !groups ? (
                <Card className={'py-6'}>
                    <CollectionErrorState onRetry={refetch} />
                </Card>
            ) : isLoading ? (
                <div className={'flex flex-col gap-2'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-24'} />
                    ))}
                </div>
            ) : !groups || groups.length === 0 ? (
                <Card className={'py-6'}>
                    <SimpleEmptyState
                        icon={IconDisc}
                        title={'No image groups'}
                        description={
                            'Create an image group to organise the operating systems servers can be built from.'
                        }
                        action={<CreateImageGroupModal />}
                    />
                </Card>
            ) : (
                <ItemGroup className={'gap-3'}>
                    {groups.map(group => (
                        <ImageGroupCard key={group.uuid} group={group} />
                    ))}
                </ItemGroup>
            )}
        </>
    )
}
