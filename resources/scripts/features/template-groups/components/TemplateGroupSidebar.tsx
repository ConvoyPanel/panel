import CreateTemplateCard from '@/features/template-groups/components/CreateTemplateCard.tsx'
import TemplateCard from '@/features/template-groups/components/TemplateCard.tsx'
import useTemplateGroupsModalStore from '@/features/template-groups/hooks/use-template-groups-modal-store.ts'
import { useTemplates } from '@/features/template-groups/templates/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { IconPlus, IconTemplate } from '@tabler/icons-react'
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

const TemplateGroupSidebar = () => {
    const {
        open: isOpen,
        data: modalData,
        close,
    } = useModal(useTemplateGroupsModalStore, 'show')
    const [isCreating, setIsCreating] = useState(false)

    const {
        data: templates,
        isLoading,
        isError,
        refetch,
    } = useTemplates(modalData?.uuid, {})

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
                        {Boolean(templates?.length) && (
                            <div className={'mb-4 flex justify-end'}>
                                <Button
                                    onClick={() => setIsCreating(true)}
                                    disabled={isCreating}
                                >
                                    <IconPlus className={'size-4'} /> New
                                    template
                                </Button>
                            </div>
                        )}
                        {isError && !templates ? (
                            <Card className={'py-6'}>
                                <CollectionErrorState onRetry={refetch} />
                            </Card>
                        ) : isLoading ? (
                            <div className={'flex flex-col gap-2'}>
                                {Array.from({ length: 4 }).map((_, index) => (
                                    <Skeleton key={index} className={'h-24'} />
                                ))}
                            </div>
                        ) : !templates || templates.length === 0 ? (
                            isCreating ? (
                                <CreateTemplateCard
                                    templateGroup={modalData}
                                    onClose={() => setIsCreating(false)}
                                />
                            ) : (
                                <Card className={'py-6'}>
                                    <SimpleEmptyState
                                        icon={IconTemplate}
                                        title={'No templates'}
                                        description={
                                            'Add a template to make it available from this group.'
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
                                                New template
                                            </Button>
                                        }
                                    />
                                </Card>
                            )
                        ) : (
                            <div className={'flex flex-col border-t'}>
                                {isCreating && (
                                    <CreateTemplateCard
                                        templateGroup={modalData}
                                        onClose={() => setIsCreating(false)}
                                    />
                                )}
                                {templates.map(template => (
                                    <TemplateCard
                                        key={template.uuid}
                                        templateGroup={modalData}
                                        template={template}
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

export default TemplateGroupSidebar
