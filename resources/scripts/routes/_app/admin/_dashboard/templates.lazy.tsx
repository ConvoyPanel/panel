import { useTemplateGroups } from '@/features/template-groups/api.ts'
import CreateTemplateGroupModal from '@/features/template-groups/components/CreateTemplateGroupModal.tsx'
import DeleteTemplateGroupModal from '@/features/template-groups/components/DeleteTemplateGroupModal.tsx'
import EditTemplateGroupModal from '@/features/template-groups/components/EditTemplateGroupModal.tsx'
import TemplateGroupCard from '@/features/template-groups/components/TemplateGroupCard.tsx'
import TemplateGroupSidebar from '@/features/template-groups/components/TemplateGroupSidebar.tsx'
import { IconTemplate } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/templates')({
    component: TemplatesIndex,
})

function TemplatesIndex() {
    const { data: groups, isLoading, isError, refetch } = useTemplateGroups({})

    return (
        <>
            <div
                className={'flex flex-wrap items-center justify-between gap-2'}
            >
                <Heading>Templates</Heading>
                {Boolean(groups?.length) && <CreateTemplateGroupModal />}
            </div>

            <EditTemplateGroupModal />
            <DeleteTemplateGroupModal />
            <TemplateGroupSidebar />

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
                        icon={IconTemplate}
                        title={'No template groups'}
                        description={
                            'Create a template group to organize installable server templates.'
                        }
                        action={<CreateTemplateGroupModal />}
                    />
                </Card>
            ) : (
                <ItemGroup className={'gap-3'}>
                    {groups.map(group => (
                        <TemplateGroupCard key={group.uuid} group={group} />
                    ))}
                </ItemGroup>
            )}
        </>
    )
}
