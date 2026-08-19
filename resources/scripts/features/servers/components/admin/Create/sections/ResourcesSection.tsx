import StoragePicker from '@/features/servers/components/admin/Create/pickers/StoragePicker'
import { IconPlus, IconTrash } from '@tabler/icons-react'
import byteSize from 'byte-size'
import { useFieldArray, useFormContext, useWatch } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { FieldFold, InputForm } from '@/components/ui/Forms'
import { Separator } from '@/components/ui/Separator'

const iec = { units: 'iec' as const, precision: 1 }

/** The form holds mebibytes; the rest of the panel prints bytes via byte-size. */
const fromMib = (value: unknown) => {
    const mib = Number(value)

    return Number.isFinite(mib) && mib > 0
        ? byteSize(mib * 1024 * 1024, iec).toString()
        : null
}

/**
 * Everything this server is allowed to consume, said in one line.
 *
 * Not a field count — the shape of the build is what an admin recognises, so
 * the summary reads the way they would say it out loud.
 */
const useResourceSummary = () => {
    const [cpu, memory, disk, bandwidth, speedLimit, backupCount, disks] =
        useWatch({
            name: [
                'cpu',
                'memory',
                'disk',
                'bandwidth',
                'speedLimit',
                'backupCount',
                'disks',
            ],
        })

    const parts: string[] = []

    if (Number(cpu) > 0) parts.push(`${Number(cpu)} vCPU`)

    const ram = fromMib(memory)
    if (ram) parts.push(ram)

    const primary = fromMib(disk)
    if (primary) parts.push(`${primary} disk`)

    if (Array.isArray(disks) && disks.length > 0) {
        parts.push(
            `+${disks.length} ${disks.length === 1 ? 'volume' : 'volumes'}`
        )
    }

    const transfer = fromMib(bandwidth)
    parts.push(transfer ? `${transfer} transfer` : 'unmetered')

    if (speedLimit) parts.push(`${speedLimit} MB/s cap`)

    const backups = Number(backupCount)
    if (backups === -1) parts.push('unlimited backups')
    else if (backups > 0) parts.push(`${backups} backups`)
    else parts.push('no backups')

    return parts.join(' · ')
}

/** Extra data disks, sized in GiB — PVE allocates whole GiB. */
const AdditionalDisks = () => {
    const { control } = useFormContext()
    const nodeId = useWatch({ name: 'nodeId' })
    const { fields, append, remove } = useFieldArray({ control, name: 'disks' })

    return (
        <div className={'space-y-3'}>
            <div className={'flex items-center justify-between gap-3'}>
                <p className={'text-sm font-medium'}>Additional disks</p>
                <Button
                    type={'button'}
                    variant={'outline'}
                    size={'sm'}
                    disabled={!nodeId}
                    onClick={() => append({ storageId: '', size: 10 })}
                >
                    <IconPlus className={'size-4'} /> Add disk
                </Button>
            </div>

            {fields.length === 0 ? (
                <p className={'text-muted-foreground text-sm'}>
                    {nodeId
                        ? 'None. The primary disk is set above.'
                        : 'Choose a node first — data disks come from its storages.'}
                </p>
            ) : (
                fields.map((field, index) => (
                    <div
                        key={field.id}
                        className={'flex items-end gap-3 rounded-lg border p-3'}
                    >
                        <div
                            className={
                                'grid flex-1 grid-cols-1 gap-3 @lg:grid-cols-2'
                            }
                        >
                            <StoragePicker
                                nodeId={nodeId ? Number(nodeId) : null}
                                requiredContentTypes={['storesKvm']}
                                name={`disks.${index}.storageId`}
                            />
                            <InputForm
                                name={`disks.${index}.size`}
                                label={'Size'}
                                type={'number'}
                                min={1}
                                suffix={'GiB'}
                            />
                        </div>
                        <Button
                            type={'button'}
                            variant={'ghost'}
                            size={'icon'}
                            className={'text-destructive mb-1'}
                            onClick={() => remove(index)}
                            aria-label={'Remove disk'}
                        >
                            <IconTrash className={'size-4'} />
                        </Button>
                    </div>
                ))
            )}
        </div>
    )
}

/**
 * Seven number boxes that almost every build accepts as they arrive — from the
 * form's own defaults, or from the preset that just filled them in. So the card
 * states the result and opens on request; see `FieldFold` for when it opens
 * itself.
 */
const ResourcesSection = () => {
    const summary = useResourceSummary()

    return (
        <Card className={'@container'}>
            <CardHeader>
                <CardTitle>Resources</CardTitle>
                <CardDescription>
                    The limits this server is built with. All of them can be
                    changed later from its settings.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <FieldFold
                    fields={[
                        'cpu',
                        'memory',
                        'disk',
                        'bandwidth',
                        'speedLimit',
                        'backupCount',
                        'backupSize',
                        'disks',
                    ]}
                    summary={summary}
                >
                    <div className={'space-y-4'}>
                        <div
                            className={'grid grid-cols-1 gap-3 @lg:grid-cols-3'}
                        >
                            <InputForm
                                name={'cpu'}
                                label={'vCPU'}
                                type={'number'}
                            />
                            <InputForm
                                name={'memory'}
                                label={'Memory'}
                                type={'number'}
                                suffix={'MiB'}
                            />
                            <InputForm
                                name={'disk'}
                                label={'Primary disk'}
                                type={'number'}
                                suffix={'MiB'}
                            />
                        </div>

                        <div
                            className={'grid grid-cols-1 gap-3 @lg:grid-cols-2'}
                        >
                            <InputForm
                                name={'bandwidth'}
                                label={'Bandwidth'}
                                type={'number'}
                                suffix={'MiB'}
                                description={'Blank is unmetered.'}
                            />
                            <InputForm
                                name={'speedLimit'}
                                label={'Speed limit'}
                                type={'number'}
                                min={1}
                                step={'any'}
                                suffix={'MB/s'}
                                description={
                                    'Caps every NIC. Blank is uncapped.'
                                }
                            />
                        </div>

                        <div
                            className={'grid grid-cols-1 gap-3 @lg:grid-cols-2'}
                        >
                            <InputForm
                                name={'backupCount'}
                                label={'Backups'}
                                type={'number'}
                                description={'-1 for unlimited.'}
                            />
                            <InputForm
                                name={'backupSize'}
                                label={'Backup storage'}
                                type={'number'}
                                suffix={'MiB'}
                                description={'-1 for unlimited.'}
                            />
                        </div>

                        <Separator />

                        <AdditionalDisks />
                    </div>
                </FieldFold>
            </CardContent>
        </Card>
    )
}

export default ResourcesSection
