import { IconPlus, IconTemplate } from '@tabler/icons-react'
import { useState } from 'react'
import { useShallow } from 'zustand/react/shallow'

import useTemplatesSWR from '@/api/admin/templateGroups/templates/use-templates-swr.ts'

import CreateTemplateCard from '@/components/interfaces/Admin/Template/CreateTemplateCard.tsx'
import TemplateCard from '@/components/interfaces/Admin/Template/TemplateCard.tsx'
import useTemplateGroupsModalStore from '@/components/interfaces/Admin/Template/use-template-groups-modal-store.ts'

import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/Sheet'
import Skeleton from '@/components/ui/Skeleton.tsx'

const TemplateGroupSidebar = () => {
    const { isOpen, modalData, closeModal } = useTemplateGroupsModalStore(
        useShallow(state => ({
            isOpen: state.activeModal === 'show',
            modalData: state.modalData,
            closeModal: state.closeModal,
        }))
    )
    const [isCreating, setIsCreating] = useState(false)

    const { data: templates, isLoading } = useTemplatesSWR(modalData?.uuid, {})

    return (
        <Sheet open={isOpen} onOpenChange={val => !val && closeModal('show')}>
            <SheetContent className={'w-11/12 overflow-y-auto sm:max-w-lg'} side={'right'}>
                <SheetHeader>
                    <SheetTitle className={'truncate'}>
                        {modalData?.name}
                    </SheetTitle>
                </SheetHeader>
                <div className={'mb-4 flex justify-end'}>
                    <Button size={'sm'} onClick={() => setIsCreating(true)} disabled={isCreating}>
                        <IconPlus className={'mr-2 size-4'} /> New
                    </Button>
                </div>
                {isLoading ? (
                    <div className={'flex flex-col gap-2'}>
                        {Array.from({ length: 4 }).map((_, index) => (
                            <Skeleton key={index} className={'h-24'} />
                        ))}
                    </div>
                ) : !templates || templates.length === 0 ? (
                    isCreating ? (
                        <CreateTemplateCard
                            templateGroup={modalData!}
                            onClose={() => setIsCreating(false)}
                        />
                    ) : (
                        <Card>
                            <CardHeader />
                            <CardContent>
                                <SimpleEmptyState
                                    icon={IconTemplate}
                                    title={'No template groups found'}
                                    description={
                                        'Create a new template group to get started.'
                                    }
                                />
                            </CardContent>
                        </Card>
                    )
                ) : (
                    <div
                        className={
                            'flex flex-col border-t'
                        }
                    >
                        {isCreating && (
                            <CreateTemplateCard
                                templateGroup={modalData!}
                                onClose={() => setIsCreating(false)}
                            />
                        )}
                        {templates.map(template => (
                            <TemplateCard
                                key={template.uuid}
                                templateGroup={modalData!}
                                template={template}
                            />
                        ))}
                    </div>
                )}
            </SheetContent>
        </Sheet>
    )
}

export default TemplateGroupSidebar
