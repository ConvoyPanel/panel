import useContainerDimensions from '@/hooks/use-container-dimensions.tsx'
import { IconBinaryTree } from '@tabler/icons-react'

import useSnapshotsSWR from '@/api/servers/snapshots/use-snapshots-swr.ts'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'


const SnapshotView = () => {
    const { data, isLoading } = useSnapshotsSWR()
    const { containerRef, dimensions } = useContainerDimensions()

    if (isLoading) {
        return <Skeleton className={'h-96 w-full'} />
    }

    if (!data) {
        return (
            <Card className={'py-6'}>
                <SimpleEmptyState
                    icon={IconBinaryTree}
                    title={'Snapshots'}
                    description={
                        'Snapshots preserve the complete state of a virtual machine, including memory, settings, and disk states, enabling easy rollback to previous working states for testing or recovery.'
                    }
                    action={<Button>Create Snapshot</Button>}
                />
            </Card>
        )
    }

    return (
        <Card className={'h-full overflow-hidden'} ref={containerRef}>
            <div id='tree' className='p-4'>
                <div className='node relative mb-2 rounded bg-blue-500 p-2 text-white'>
                    Snapshot 1
                    <div className='ml-2 border-l border-gray-400 pl-6'>
                        <div className='node mb-2 rounded bg-blue-500 p-2 text-white'>
                            Snapshot 1.1
                        </div>
                        <div className='node mb-2 rounded bg-blue-500 p-2 text-white'>
                            Snapshot 1.2
                            <div className='ml-2 border-l border-gray-400 pl-6'>
                                <div className='node mb-2 rounded bg-blue-500 p-2 text-white'>
                                    Snapshot 1.2.1
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className='node mb-2 rounded bg-blue-500 p-2 text-white'>
                    Snapshot 2
                </div>
            </div>
            {/*<Tree*/}
            {/*    dimensions={dimensions}*/}
            {/*    collapsible={false}*/}
            {/*    translate={{*/}
            {/*        x: dimensions.width / 2,*/}
            {/*        y: dimensions.height / 10,*/}
            {/*    }}*/}
            {/*    zoomable={false}*/}
            {/*    // @ts-ignore*/}
            {/*    renderCustomNodeElement={SnapshotNode}*/}
            {/*    data={data}*/}
            {/*    orientation={'vertical'}*/}
            {/*/>*/}
        </Card>
    )
}

export default SnapshotView
