import CreateImageCard from '@/features/images/components/CreateImageCard.tsx'
import ImageCard from '@/features/images/components/ImageCard.tsx'
import { useImageDefinitions } from '@/features/images/definitions/api.ts'
import useImageGroupsModalStore from '@/features/images/hooks/use-image-groups-modal-store.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { IconDisc, IconPlus } from '@tabler/icons-react'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/Sheet'
import Skeleton from '@/components/ui/Skeleton.tsx'

const ImageGroupSidebar = () => {
    const {
        open: isOpen,
        data: modalData,
        close,
    } = useModal(useImageGroupsModalStore, 'show')
    const [isCreating, setIsCreating] = useState(false)

    const {
        data: definitions,
        isLoading,
        isError,
        refetch,
    } = useImageDefinitions(modalData?.uuid, {})

    return (
        <Sheet open={isOpen} onOpenChange={val => !val && close()}>
            <SheetContent
                className={'w-11/12 overflow-y-auto sm:max-w-lg'}
                side={'right'}
            >
                {/* Guard the contents, not the <Sheet>: the root has to stay
                    mounted across opens or it never plays its enter transition,
                    but nothing inside it is meaningful without a group. */}
                {modalData && (
                    <>
                        <SheetHeader>
                            <SheetTitle className={'truncate'}>
                                {modalData.name}
                            </SheetTitle>
                        </SheetHeader>
                        {Boolean(definitions?.length) && (
                            <div className={'mb-4 flex justify-end'}>
                                <Button
                                    onClick={() => setIsCreating(true)}
                                    disabled={isCreating}
                                >
                                    <IconPlus className={'size-4'} /> New image
                                </Button>
                            </div>
                        )}
                        {isError && !definitions ? (
                            <Card className={'py-6'}>
                                <CollectionErrorState onRetry={refetch} />
                            </Card>
                        ) : isLoading ? (
                            <div className={'flex flex-col gap-2'}>
                                {Array.from({ length: 4 }).map((_, index) => (
                                    <Skeleton key={index} className={'h-24'} />
                                ))}
                            </div>
                        ) : !definitions || definitions.length === 0 ? (
                            isCreating ? (
                                <CreateImageCard
                                    imageGroup={modalData}
                                    onClose={() => setIsCreating(false)}
                                />
                            ) : (
                                <Card className={'py-6'}>
                                    <SimpleEmptyState
                                        icon={IconDisc}
                                        title={'No images'}
                                        description={
                                            'Add an image to make it available from this group.'
                                        }
                                        action={
                                            <Button
                                                onClick={() =>
                                                    setIsCreating(true)
                                                }
                                            >
                                                <IconPlus
                                                    className={'size-4'}
                                                />{' '}
                                                New image
                                            </Button>
                                        }
                                    />
                                </Card>
                            )
                        ) : (
                            <div className={'flex flex-col border-t'}>
                                {isCreating && (
                                    <CreateImageCard
                                        imageGroup={modalData}
                                        onClose={() => setIsCreating(false)}
                                    />
                                )}
                                {definitions.map(image => (
                                    <ImageCard
                                        key={image.uuid}
                                        imageGroup={modalData}
                                        image={image}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </SheetContent>
        </Sheet>
    )
}

export default ImageGroupSidebar
