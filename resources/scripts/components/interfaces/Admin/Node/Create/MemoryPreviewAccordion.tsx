import byteSize from 'byte-size'
import { useMemo } from 'react'
import { useFormContext } from 'react-hook-form'
import { z } from 'zod'

import { nodeSchema } from '@/api/admin/nodes/createNode.ts'

import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/Accordion'

const MemoryPreviewAccordion = () => {
    const { watch } = useFormContext<z.infer<typeof nodeSchema>>()
    const memory = Number(watch('memory')) * 1024 * 1024
    const memoryOverallocate = Number(watch('memoryOverallocate'))


    const formattedMemory = useMemo(() => {
        return byteSize(memory, {
            units: 'iec',
            precision: 1,
        })
    }, [memory])

    const formattedMemoryOverallocate = useMemo(() => {
        return byteSize(memory * (memoryOverallocate / 100), {
            units: 'iec',
            precision: 1,
        })
    }, [memory, memoryOverallocate])

    const formattedTotalMemory = useMemo(() => {
        return byteSize(memory * (1 + memoryOverallocate / 100), {
            units: 'iec',
            precision: 1,
        })
    }, [memory, memoryOverallocate])

    return (
        <Accordion type={'single'} collapsible className={'w-full'}>
            <AccordionItem value={'preview'}>
                <AccordionTrigger>Preview</AccordionTrigger>
                <AccordionContent>
                    <dl className={'flex flex-col sm:flex-row space-y-3 sm:space-y-0'}>
                        <div className={'flex flex-col min-w-[9rem]'}>
                            <dd
                                className={
                                    'text-3xl font-semibold text-foreground'
                                }
                            >
                                {formattedMemory.value}{' '}
                                <span className={'text-sm'}>
                                    {formattedMemory.unit}
                                </span>
                            </dd>
                            <dt className={'text-xs text-muted-foreground'}>
                                Memory
                            </dt>
                        </div>
                        <div className={'flex flex-col min-w-[9rem]'}>
                            <dd
                                className={
                                    'text-3xl font-semibold text-foreground'
                                }
                            >
                                {formattedMemoryOverallocate.value}{' '}
                                <span className={'text-sm'}>
                                    {formattedMemoryOverallocate.unit}
                                </span>
                            </dd>
                            <dt className={'text-xs text-muted-foreground'}>
                                Overallocation
                            </dt>
                        </div>
                        <div className={'flex flex-col min-w-[9rem]'}>
                            <dd
                                className={
                                    'text-3xl font-semibold text-foreground'
                                }
                            >
                                {formattedTotalMemory.value}{' '}
                                <span className={'text-sm'}>
                                    {formattedTotalMemory.unit}
                                </span>
                            </dd>
                            <dt className={'text-xs text-muted-foreground'}>
                                Total
                            </dt>
                        </div>
                    </dl>
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    )
}

export default MemoryPreviewAccordion
