import { Template, TemplateGroup } from '@/api/admin/nodes/templates/getTemplates'
import useTemplatesSWR from '@/api/admin/nodes/templates/useTemplatesSWR'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import Button from '@/components/elements/Button'
import Card from '@/components/elements/Card'
import Spinner from '@/components/elements/Spinner'
import { NodeContext } from '@/state/admin/node'
import { useEffect, useState } from 'react'

const NodeTemplatesContainer = () => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { data } = useTemplatesSWR(nodeId)
    const [templateGroups, setTemplateGroups] = useState<TemplateGroup[]>([])
    useEffect(() => {
        if (data) {
            setTemplateGroups(data)
        }
    }, [data])

    return (
        <NodeContentBlock title='Templates' showFlashKey='admin:node:templates'>
            <div className='flex justify-end items-center mb-3'>
                <Button variant='filled'>
                    New Template Group
                </Button>
            </div>
            {!data ? (
                <Spinner />
            ) : (
                <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3'>
                    {data.map(templateGroups => (
                        <Card overridePadding className='px-4 py-3 min-h-[9rem]' key={templateGroups.id}>
                            <p className='font-medium text-foreground'>{templateGroups.name}</p>
                            <div className='flex flex-col space-y-3 mt-2'>
                                {templateGroups.templates.map(template => (
                                    <div className='px-3 py-2 bg-accent-200 rounded' key={template.id}>
                                        <p className='font-medium text-sm text-foreground'>{template.name}</p>
                                        <p className='description-small !text-xs'>vmid: {template.vmid}</p>
                                    </div>
                                ))}

                                <button className='text-sm bg-accent-100 text-foreground border border-accent-400 border-dashed rounded py-2'>
                                    New Template
                                </button>
                            </div>
                        </Card>
                    ))}
                    <button className='text-center border border-accent-400 border-dashed bg-transparent hover:bg-accent-200 hover:text-foreground transition-colors rounded p-12'>
                        New Template Group
                    </button>
                </div>
            )}
        </NodeContentBlock>
    )
}

export default NodeTemplatesContainer
