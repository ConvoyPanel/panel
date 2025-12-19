import { Node } from '@/types/node.ts'
import { cn } from '@/utils'
import { IconCheck, IconServer } from '@tabler/icons-react'
import SimpleEmptyState from '@/components/ui/EmptyStates/SimpleEmptyState'
import { useController } from 'react-hook-form'

import getNodes from '@/api/admin/nodes/getNodes.ts'
import useNodeSWR from '@/api/admin/nodes/use-node-swr'
import { ResourceComboboxForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'

const NodePicker = () => {
    const { field } = useController<{
        nodeId: string
    }>({
        name: 'nodeId',
    })
    const { data: selected, isLoading: isLoadingSelection } = useNodeSWR(
        field.value ? Number(field.value) : undefined
    )

    return (
        <ResourceComboboxForm<Node>
            swrKey={'nodes'}
            accessorKey={'id'}
            name={'nodeId'}
            fetcher={(query, page) =>
                getNodes({
                    page: page,
                    filters: {
                        '*': query,
                    },
                })
            }
            renderItem={(item, isSelected) => (
                <>
                    <dl className={'flex grow flex-col overflow-hidden'}>
                        <dt className={'truncate'}>{item.displayName}</dt>
                        <dd
                            className={'truncate text-xs text-muted-foreground'}
                        >
                            {item.name}
                        </dd>
                    </dl>

                    <IconCheck
                        className={cn(
                            'shrink-0',
                            isSelected ? 'opacity-100' : 'opacity-0'
                        )}
                    />
                </>
            )}
            renderTrigger={() => (
                <>
                    {isLoadingSelection ? (
                        <Skeleton className={'h-3 w-24'} />
                    ) : selected ? (
                        selected.displayName
                    ) : (
                        'Select a node'
                    )}

                    <IconServer className={'ml-auto size-4 opacity-50'} />
                </>
            )}
            label={'Node'}
            searchPlaceholder={'Search nodes...'}
            nothingFoundMessage={
                <div className="p-2">
                    <SimpleEmptyState
                        icon={IconServer}
                        title="No Nodes"
                        description="There are no nodes available matching your criteria."
                    />
                </div>
            }
        />
    )
}

export default NodePicker
