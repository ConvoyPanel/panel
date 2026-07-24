import { TemplateGroup } from '@/types/template-group.ts'
import { Template } from '@/types/template.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconDots, IconLockFilled } from '@tabler/icons-react'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import useQueryMutator from '@/hooks/use-query-mutator.ts'

import {
    templateQueries,
    templateSchema,
    updateTemplate,
    useDeleteTemplateMutation,
} from '@/features/template-groups/templates/api.ts'

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
    InputForm,
    TextareaForm,
} from '@/components/ui/Forms'

interface Props {
    templateGroup: TemplateGroup
    template: Template
}

const TemplateCard = ({ templateGroup, template }: Props) => {
    const [isEditing, setIsEditing] = useState(false)
    const [isDeleting, setIsDeleting] = useState(false)
    const mutate = useQueryMutator<Template[]>(
        templateQueries.list(templateGroup.uuid, {}).queryKey
    )

    const deleteTemplateMutation = useDeleteTemplateMutation(templateGroup.uuid)

    const form = useForm<z.input<typeof templateSchema>>({
        resolver: zodResolver(templateSchema),
    })

    useEffect(() => {
        form.reset({
            name: template.name,
            description: template.description ?? '',
            vmid: template.vmid,
            isAdminOnly: template.isAdminOnly,
        })
    }, [template])

    const save = async (data: z.input<typeof templateSchema>) => {
        try {
            const updatedTemplate = await updateTemplate(
                templateGroup.uuid,
                template.uuid,
                data as z.infer<typeof templateSchema>
            )

            await mutate(data => {
                if (!data) return data

                return data.map(t =>
                    t.uuid === updatedTemplate.uuid ? updatedTemplate : t
                )
            })

            setIsEditing(false)
        } catch (e) {
            if (!handleFormErrors(e, form.setError)) {
                toast.error('Failed to save changes')
            }
        }
    }

    const del = async () => {
        try {
            await deleteTemplateMutation.trigger(template.uuid)
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
                        <InputForm
                            name={'vmid'}
                            label={'VMID'}
                            type={'number'}
                        />
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
            <div className={'flex min-w-0 grow flex-col justify-center'}>
                <h3 className={'flex items-center gap-1 font-semibold'}>
                    <span className={'truncate'}>{template.name}</span>{' '}
                    {template.isAdminOnly && (
                        <IconLockFilled className={'-mt-0.5 size-4 shrink-0'} />
                    )}
                    <span
                        className={`${badgeVariants({
                            variant: 'secondary',
                        })} shrink-0`}
                    >
                        {template.vmid}
                    </span>
                </h3>
                {template.description && (
                    <p className={'truncate text-sm text-muted-foreground'}>
                        {template.description}
                    </p>
                )}

                {isDeleting && (
                    <>
                        <p className={'text-sm text-destructive'}>
                            Are you sure you want to delete this template?
                        </p>

                        <div className={'mt-2 flex justify-end gap-2'}>
                            <Button
                                variant={'secondary'}
                                onClick={() => setIsDeleting(false)}
                                disabled={deleteTemplateMutation.isMutating}
                            >
                                Cancel
                            </Button>
                            <Button
                                variant={'destructive'}
                                onClick={del}
                                loading={deleteTemplateMutation.isMutating}
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
                            aria-label={'Open template actions'}
                        >
                            <IconDots
                                className={'size-4 text-muted-foreground'}
                            />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem onClick={() => setIsEditing(true)}>
                            Edit
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

export default TemplateCard
