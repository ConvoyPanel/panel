import {
    imageDefinitionQueries,
    imageDefinitionSchema,
    updateImageDefinition,
    useDeleteImageDefinitionMutation,
} from '@/features/images/definitions/api.ts'
import HardwareForm from '@/features/images/hardware/HardwareForm'
import { OSTYPE_ITEMS } from '@/features/images/ostypes'
import ImageVersionsModal from '@/features/images/versions/ImageVersionsModal'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { ImageGroup } from '@/types/image.ts'
import { ImageDefinition } from '@/types/image.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCpu, IconDots, IconLockFilled } from '@tabler/icons-react'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { badgeVariants } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { Form, FormButton } from '@/components/ui/Form'
import {
    CheckboxItemForm,
    GroupHeader,
    InputForm,
    SelectForm,
    TextareaForm,
} from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

interface Props {
    imageGroup: ImageGroup
    image: ImageDefinition
}

const ImageCard = ({ imageGroup, image }: Props) => {
    const [isEditing, setIsEditing] = useState(false)
    const [isDeleting, setIsDeleting] = useState(false)
    const [showingVersions, setShowingVersions] = useState(false)
    const mutate = useQueryMutator<ImageDefinition[]>(
        imageDefinitionQueries.list(imageGroup.uuid, {}).queryKey
    )

    const deleteImageMutation = useDeleteImageDefinitionMutation(
        imageGroup.uuid
    )

    const form = useForm<z.input<typeof imageDefinitionSchema>>({
        resolver: zodResolver(imageDefinitionSchema),
    })

    // The hardware form's defaults depend on it, so it is watched rather than
    // read once: switching to Windows has to change what "inherited" means.
    const ostype = form.watch('ostype') ?? image.ostype

    useEffect(() => {
        form.reset({
            name: image.name,
            description: image.description ?? '',
            ostype: image.ostype,
            hardware: image.hardware ?? {},
            minimumCores: image.minimumCores,
            minimumMemory: image.minimumMemory,
            isAdminOnly: image.isAdminOnly,
        })
    }, [image])

    const save = async (data: z.input<typeof imageDefinitionSchema>) => {
        try {
            const updatedImageDefinition = await updateImageDefinition(
                imageGroup.uuid,
                image.uuid,
                data as z.infer<typeof imageDefinitionSchema>
            )

            await mutate(data => {
                if (!data) return data

                return data.map(t =>
                    t.uuid === updatedImageDefinition.uuid
                        ? updatedImageDefinition
                        : t
                )
            })

            setIsEditing(false)
        } catch (e) {
            if (!handleFormErrors(e, form.setError)) {
                toast.add({ title: 'Failed to save changes', type: 'error' })
            }
        }
    }

    const del = async () => {
        try {
            await deleteImageMutation.trigger(image.uuid)
            setIsDeleting(false)
        } catch (error) {
            // Error handling is done in the mutation hook
        }
    }

    if (isEditing) {
        return (
            <div className={'border-b py-2'}>
                <Form {...form}>
                    <form
                        className={'space-y-2'}
                        onSubmit={form.handleSubmit(save)}
                    >
                        <InputForm name={'name'} label={'Name'} />
                        <TextareaForm
                            name={'description'}
                            label={'Description'}
                            description={'This is visible to non-admins too.'}
                        />
                        <SelectForm
                            name={'ostype'}
                            label={'Operating system'}
                            description={
                                'Proxmox derives the cloud-init drive type from this, so it has to match the guest.'
                            }
                            items={OSTYPE_ITEMS}
                        />
                        <div className={'space-y-2'}>
                            <GroupHeader
                                icon={<IconCpu className={'size-4'} />}
                                title={'Hardware'}
                            />
                            <p className={'text-muted-foreground text-sm'}>
                                Every field below already has a working value
                                from the operating system. Change one only if
                                this image needs something different.
                            </p>
                            <HardwareForm ostype={ostype} />
                        </div>
                        <CheckboxItemForm
                            name={'isAdminOnly'}
                            label={'Admin Only'}
                        />

                        <div className={'flex justify-end gap-2'}>
                            <Button
                                variant={'secondary'}
                                onClick={() => {
                                    setIsEditing(false)
                                    form.reset()
                                }}
                                type={'button'}
                            >
                                Cancel
                            </Button>
                            <FormButton>Save</FormButton>
                        </div>
                    </form>
                </Form>
            </div>
        )
    }

    return (
        <div className={'flex min-h-[3.75rem] items-center border-b py-2'}>
            <ImageVersionsModal
                imageGroup={imageGroup}
                image={image}
                open={showingVersions}
                onOpenChange={setShowingVersions}
            />
            <div className={'flex min-w-0 grow flex-col justify-center'}>
                <h3 className={'flex items-center gap-1 font-semibold'}>
                    <span className={'truncate'}>{image.name}</span>{' '}
                    {image.isAdminOnly && (
                        <IconLockFilled className={'-mt-0.5 size-4 shrink-0'} />
                    )}
                    <span
                        className={`${badgeVariants({
                            variant: 'secondary',
                        })} shrink-0`}
                    >
                        {image.latestVersion?.version ?? 'no version'}
                    </span>
                </h3>
                {image.description && (
                    <p className={'text-muted-foreground truncate text-sm'}>
                        {image.description}
                    </p>
                )}

                {isDeleting && (
                    <>
                        <p className={'text-destructive text-sm'}>
                            Are you sure you want to delete this image?
                        </p>

                        <div className={'mt-2 flex justify-end gap-2'}>
                            <Button
                                variant={'secondary'}
                                onClick={() => setIsDeleting(false)}
                                disabled={deleteImageMutation.isMutating}
                            >
                                Cancel
                            </Button>
                            <Button
                                variant={'destructive'}
                                onClick={del}
                                loading={deleteImageMutation.isMutating}
                            >
                                Delete
                            </Button>
                        </div>
                    </>
                )}
            </div>
            {!isDeleting && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            className={'shrink-0'}
                            size={'icon'}
                            variant={'ghost'}
                            aria-label={'Open image actions'}
                        >
                            <IconDots
                                className={'text-muted-foreground size-4'}
                            />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem onClick={() => setIsEditing(true)}>
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => setShowingVersions(true)}
                        >
                            Versions
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant={'destructive'}
                            onClick={() => setIsDeleting(true)}
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    )
}

export default ImageCard
