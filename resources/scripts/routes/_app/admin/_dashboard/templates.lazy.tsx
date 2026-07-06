import { IconTemplate } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { useTemplateGroups } from '@/features/template-groups/api.ts'

import CreateTemplateGroupModal from '@/features/template-groups/components/CreateTemplateGroupModal.tsx'
import DeleteTemplateGroupModal from '@/features/template-groups/components/DeleteTemplateGroupModal.tsx'
import EditTemplateGroupModal from '@/features/template-groups/components/EditTemplateGroupModal.tsx'
import TemplateGroupCard from '@/features/template-groups/components/TemplateGroupCard.tsx'
import TemplateGroupSidebar from '@/features/template-groups/components/TemplateGroupSidebar.tsx'

import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/templates')({
    component: TemplatesIndex,
})

function TemplatesIndex() {
    const { data: groups, isLoading } = useTemplateGroups({})

    return (
        <>
            <Heading>Templates</Heading>

            <CreateTemplateGroupModal />
            <EditTemplateGroupModal />
            <DeleteTemplateGroupModal />
            <TemplateGroupSidebar />

            {isLoading ? (
                <div className={'flex flex-col gap-2'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-24'} />
                    ))}
                </div>
            ) : groups!.length === 0 ? (
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
            ) : (
                <div className={'flex flex-col gap-2'}>
                    {groups!.map(group => (
                        <TemplateGroupCard key={group.uuid} group={group} />
                    ))}
                </div>
            )}
        </>
    )
}
