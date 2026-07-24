import { IconPlus, IconTrash } from '@tabler/icons-react'
import { useFieldArray, useFormContext } from 'react-hook-form'

import StoragePicker from '@/features/servers/components/admin/Create/pickers/StoragePicker'

import { Button } from '@/components/ui/Button'
import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

// Secondary/data disks added at creation time. The primary OS disk lives on the
// General/Limits forms; these are extra volumes, each on its own storage, sized
// in GiB (PVE allocates whole GiB). They map to `limits.disks[]` on submit.
const SecondaryDisksForm = () => {
    const { control, watch } = useFormContext()
    const nodeId = watch('nodeId')
    const { fields, append, remove } = useFieldArray({
        control,
        name: 'disks',
    })

    return (
        <div className={'flex flex-col space-y-4'}>
            <div className={'flex items-center justify-between'}>
                <Heading as={'h3'}>Additional Disks</Heading>
                <Button
                    type={'button'}
                    variant={'outline'}
                    disabled={!nodeId}
                    onClick={() => append({ storageId: '', size: 10 })}
                >
                    <IconPlus className={'size-4'} /> Add disk
                </Button>
            </div>

            {fields.length === 0 ? (
                <p className={'text-sm text-muted-foreground'}>
                    {nodeId
                        ? 'No additional disks. The primary disk is configured above.'
                        : 'Select a node first to add data disks.'}
                </p>
            ) : (
                <div className={'flex flex-col space-y-4'}>
                    {fields.map((field, index) => (
                        <div
                            key={field.id}
                            className={
                                'flex items-end gap-3 rounded-lg border p-4'
                            }
                        >
                            <div className={'grid flex-1 grid-cols-2 gap-4'}>
                                <StoragePicker
                                    nodeId={nodeId ? Number(nodeId) : null}
                                    requiredContentTypes={['storesKvm']}
                                    name={`disks.${index}.storageId`}
                                />
                                <InputForm
                                    name={`disks.${index}.size`}
                                    label={'Size (GiB)'}
                                    type={'number'}
                                    min={1}
                                />
                            </div>
                            <Button
                                type={'button'}
                                variant={'ghost'}
                                size={'icon'}
                                className={'mb-1 text-destructive'}
                                onClick={() => remove(index)}
                                aria-label={'Remove disk'}
                            >
                                <IconTrash className={'size-4'} />
                            </Button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    )
}

export default SecondaryDisksForm
