import { uploadImageDisk } from '@/features/images/hardware/api'
import {
    createImageVersion,
    deleteImageVersion,
    imageVersionQueries,
    imageVersionSchema,
    setImageVersionActive,
    useImageVersions,
} from '@/features/images/versions/api'
import { formatBytes } from '@/features/servers/storage/api.ts'
import { ImageDefinition, ImageDiskRole, ImageGroup } from '@/types/image.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCloudUpload, IconLink, IconTrash } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, SwitchForm } from '@/components/ui/Forms'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    imageGroup: ImageGroup
    image: ImageDefinition
    open: boolean
    onOpenChange: (open: boolean) => void
}

type DiskDraft = {
    slot: string
    role: ImageDiskRole
    url: string | null
    path: string | null
    sha256: string
    size: number
    virtualSize: number
    format: string
}

const emptySystemDisk = (): DiskDraft => ({
    slot: 'scsi0',
    role: ImageDiskRole.SYSTEM,
    url: null,
    path: null,
    sha256: '',
    size: 0,
    virtualSize: 0,
    format: 'qcow2',
})

/**
 * Publishes builds of an image, and retires old ones.
 *
 * A version is never edited. Its disks are what a server was built from, so
 * changing them would rewrite the answer to "where did this machine come from"
 * for every server already pointing at it — a corrected build is a new version,
 * and an obsolete one is retired rather than deleted.
 */
const ImageVersionsModal = ({
    imageGroup,
    image,
    open,
    onOpenChange,
}: Props) => {
    const queryClient = useQueryClient()
    const { data: versions } = useImageVersions(imageGroup.uuid, image.uuid)
    const [disks, setDisks] = useState<DiskDraft[]>([emptySystemDisk()])
    const [uploading, setUploading] = useState<number | null>(null)
    const [progress, setProgress] = useState(0)

    const form = useForm<z.input<typeof imageVersionSchema>>({
        resolver: zodResolver(imageVersionSchema),
        defaultValues: { version: '', isActive: true, disks: [] },
    })

    const refresh = () =>
        queryClient.invalidateQueries({
            queryKey: imageVersionQueries.all(imageGroup.uuid, image.uuid),
        })

    const patch = (index: number, values: Partial<DiskDraft>) =>
        setDisks(current =>
            current.map((disk, i) =>
                i === index ? { ...disk, ...values } : disk
            )
        )

    /**
     * Uploading answers three questions at once — the hash, the transfer size
     * and the provisioned size — none of which anyone should be typing.
     */
    const upload = async (index: number, file: File) => {
        setUploading(index)
        setProgress(0)

        try {
            const uploaded = await uploadImageDisk(file, setProgress)

            patch(index, {
                url: null,
                path: uploaded.path,
                sha256: uploaded.sha256,
                size: uploaded.size,
                virtualSize: uploaded.virtualSize,
                format: uploaded.format,
            })
        } catch {
            toast.add({ title: 'Failed to upload the disk', type: 'error' })
        } finally {
            setUploading(null)
        }
    }

    const submit = async (data: z.input<typeof imageVersionSchema>) => {
        try {
            await createImageVersion(imageGroup.uuid, image.uuid, {
                ...(data as z.infer<typeof imageVersionSchema>),
                disks: disks as any,
            })

            await refresh()
            form.reset()
            setDisks([emptySystemDisk()])
            toast.add({ title: 'Version published', type: 'success' })
        } catch (e) {
            if (!handleFormErrors(e, form.setError)) {
                toast.add({ title: 'Failed to publish version', type: 'error' })
            }
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {image.name} versions
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody className={'space-y-6'}>
                    <div className={'space-y-2'}>
                        {versions?.length === 0 && (
                            <p className={'text-muted-foreground text-sm'}>
                                No versions yet. Until one is published, this
                                image cannot be installed.
                            </p>
                        )}

                        {versions?.map(version => (
                            <div
                                key={version.uuid}
                                className={
                                    'flex items-center gap-3 border-b py-2'
                                }
                            >
                                <div className={'min-w-0 grow'}>
                                    <p className={'font-semibold'}>
                                        {version.version}
                                        {!version.isActive && ' · retired'}
                                    </p>
                                    <p
                                        className={
                                            'text-muted-foreground text-sm'
                                        }
                                    >
                                        {formatBytes(version.size)} to transfer
                                        · {formatBytes(version.minimumDisk)}{' '}
                                        provisioned
                                    </p>
                                </div>
                                <Button
                                    type={'button'}
                                    variant={'secondary'}
                                    size={'sm'}
                                    onClick={async () => {
                                        await setImageVersionActive(
                                            imageGroup.uuid,
                                            image.uuid,
                                            version.uuid,
                                            !version.isActive
                                        )
                                        await refresh()
                                    }}
                                >
                                    {version.isActive ? 'Retire' : 'Restore'}
                                </Button>
                                <Button
                                    type={'button'}
                                    variant={'ghost'}
                                    size={'icon'}
                                    aria-label={'Delete version'}
                                    onClick={async () => {
                                        try {
                                            await deleteImageVersion(
                                                imageGroup.uuid,
                                                image.uuid,
                                                version.uuid
                                            )
                                            await refresh()
                                        } catch {
                                            toast.add({
                                                title: 'Servers were built from this version — retire it instead.',
                                                type: 'error',
                                            })
                                        }
                                    }}
                                >
                                    <IconTrash className={'size-4'} />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <Form {...form}>
                        <form
                            className={'space-y-4'}
                            onSubmit={form.handleSubmit(submit)}
                        >
                            <InputForm
                                name={'version'}
                                label={'Version'}
                                description={'major.minor.patch, e.g. 1.0.0'}
                            />
                            <SwitchForm
                                name={'isActive'}
                                label={'Offer this version to new servers'}
                            />

                            {disks.map((disk, index) => (
                                <DiskRow
                                    key={index}
                                    disk={disk}
                                    uploading={uploading === index}
                                    progress={progress}
                                    onChange={values => patch(index, values)}
                                    onUpload={file => upload(index, file)}
                                    onRemove={
                                        disk.role === ImageDiskRole.SYSTEM
                                            ? undefined
                                            : () =>
                                                  setDisks(current =>
                                                      current.filter(
                                                          (_, i) => i !== index
                                                      )
                                                  )
                                    }
                                />
                            ))}

                            {!disks.some(
                                d => d.role === ImageDiskRole.EFIVARS
                            ) && (
                                <Button
                                    type={'button'}
                                    variant={'secondary'}
                                    size={'sm'}
                                    onClick={() =>
                                        setDisks(current => [
                                            ...current,
                                            {
                                                ...emptySystemDisk(),
                                                slot: 'efidisk0',
                                                role: ImageDiskRole.EFIVARS,
                                            },
                                        ])
                                    }
                                >
                                    Add a UEFI varstore
                                </Button>
                            )}

                            <div className={'flex justify-end'}>
                                <FormButton>Publish version</FormButton>
                            </div>
                        </form>
                    </Form>
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

const DiskRow = ({
    disk,
    uploading,
    progress,
    onChange,
    onUpload,
    onRemove,
}: {
    disk: DiskDraft
    uploading: boolean
    progress: number
    onChange: (values: Partial<DiskDraft>) => void
    onUpload: (file: File) => void
    onRemove?: () => void
}) => (
    <div className={'space-y-2 rounded-md border p-3'}>
        <div className={'flex items-center justify-between'}>
            <Label>
                {disk.role === ImageDiskRole.SYSTEM
                    ? 'System disk'
                    : 'UEFI varstore'}{' '}
                <span className={'text-muted-foreground font-mono text-xs'}>
                    {disk.slot}
                </span>
            </Label>
            {onRemove && (
                <button
                    type={'button'}
                    className={'text-muted-foreground text-xs underline'}
                    onClick={onRemove}
                >
                    Remove
                </button>
            )}
        </div>

        {disk.path ? (
            <p className={'text-muted-foreground text-sm'}>
                <IconCloudUpload className={'mr-1 inline size-4'} />
                Hosted by Convoy · {formatBytes(disk.size)} ·{' '}
                {formatBytes(disk.virtualSize)} provisioned
            </p>
        ) : (
            <>
                <div className={'flex items-center gap-2'}>
                    <IconLink className={'text-muted-foreground size-4'} />
                    <Input
                        placeholder={'https://…/image.qcow2'}
                        value={disk.url ?? ''}
                        onChange={event =>
                            onChange({
                                url: event.target.value || null,
                                path: null,
                            })
                        }
                    />
                </div>
                <p className={'text-muted-foreground text-xs'}>
                    Or upload the file and Convoy will serve it to your nodes.
                </p>
                <Input
                    type={'file'}
                    accept={'.qcow2,.img,.raw'}
                    disabled={uploading}
                    onChange={event => {
                        const file = event.target.files?.[0]
                        if (file) onUpload(file)
                    }}
                />
                {uploading && (
                    <p className={'text-muted-foreground text-xs'}>
                        Uploading… {Math.round(progress * 100)}%
                    </p>
                )}
            </>
        )}

        {/* Only needed for a link: an upload is hashed and measured on arrival. */}
        {!disk.path && (
            <div className={'grid gap-2 sm:grid-cols-3'}>
                <div className={'space-y-1'}>
                    <Label className={'text-xs'}>SHA-256</Label>
                    <Input
                        value={disk.sha256}
                        onChange={event =>
                            onChange({ sha256: event.target.value.trim() })
                        }
                    />
                </div>
                <div className={'space-y-1'}>
                    <Label className={'text-xs'}>File size (bytes)</Label>
                    <Input
                        value={disk.size || ''}
                        onChange={event =>
                            onChange({ size: Number(event.target.value) })
                        }
                    />
                </div>
                <div className={'space-y-1'}>
                    <Label className={'text-xs'}>
                        Provisioned size (bytes)
                    </Label>
                    <Input
                        value={disk.virtualSize || ''}
                        onChange={event =>
                            onChange({
                                virtualSize: Number(event.target.value),
                            })
                        }
                    />
                </div>
            </div>
        )}
    </div>
)

export default ImageVersionsModal
