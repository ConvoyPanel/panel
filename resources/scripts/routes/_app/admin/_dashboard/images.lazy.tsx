import { useImageGroups } from '@/features/images/api.ts'
import CreateImageGroupModal from '@/features/images/components/CreateImageGroupModal.tsx'
import DeleteImageGroupModal from '@/features/images/components/DeleteImageGroupModal.tsx'
import EditImageGroupModal from '@/features/images/components/EditImageGroupModal.tsx'
import ImageGroupCard from '@/features/images/components/ImageGroupCard.tsx'
import ImageGroupSidebar from '@/features/images/components/ImageGroupSidebar.tsx'
import ImageCatalogSheet from '@/features/images/registry/ImageCatalogSheet.tsx'
import { IconDisc, IconLibrary } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import { PageToolbar } from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

const ImagesIndex = () => {
    const { data: groups, isLoading, isError, refetch } = useImageGroups({})
    const [browsing, setBrowsing] = useState(false)

    const browse = (
        <Button variant={'secondary'} onClick={() => setBrowsing(true)}>
            <IconLibrary className={'size-4'} /> Browse catalogue
        </Button>
    )

    return (
        <>
            <Heading>Images</Heading>
            {Boolean(groups?.length) && (
                <PageToolbar
                    actions={
                        <>
                            {browse}
                            <CreateImageGroupModal />
                        </>
                    }
                />
            )}

            <EditImageGroupModal />
            <DeleteImageGroupModal />
            <ImageGroupSidebar />
            <ImageCatalogSheet open={browsing} onOpenChange={setBrowsing} />

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
                            'Import from the catalogue, or create a group of your own.'
                        }
                        action={
                            <>
                                {browse}
                                <CreateImageGroupModal />
                            </>
                        }
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

export const Route = createLazyFileRoute('/_app/admin/_dashboard/images')({
    component: ImagesIndex,
})

export default ImagesIndex
