import { useMemo, useEffect } from 'react'
import { Server } from '@/types/server'
import { toast } from 'sonner'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'

import { Form, FormButton } from '@/components/ui/Form'
import InputForm from '@/components/ui/Forms/InputForm'
import SelectForm from '@/components/ui/Forms/SelectForm'
import useServerSWR from '@/api/servers/use-server-swr'
import reinstallServer from '@/api/servers/reinstallServer'
import useTemplateGroupsSWR from '@/api/servers/use-template-groups-swr'
import TemplateIconDisplay from '@/components/interfaces/Admin/Template/TemplateIconDisplay'

interface OSSelectionFormProps {
    server: Server
}

const formSchema = z.object({
    templateGroupUuid: z.string().min(1, 'Please select an OS family'),
    templateUuid: z.string().min(1, 'Please select a version'),
    accountPassword: z.string().min(8, 'Password must be at least 8 characters'),
})

type FormData = z.infer<typeof formSchema>

export default function OSSelectionForm({ server }: OSSelectionFormProps) {
    const { mutate } = useServerSWR()
    const { data: templateGroups } = useTemplateGroupsSWR(server.uuid)

    const form = useForm<FormData>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            templateGroupUuid: '',
            templateUuid: '',
            accountPassword: '',
        },
    })

    const selectedTemplateGroupUuid = form.watch('templateGroupUuid')

    useEffect(() => {
        form.setValue('templateUuid', '')
    }, [selectedTemplateGroupUuid, form])

    const templateGroupItems = useMemo(
        () =>
            templateGroups?.map(group => ({
                value: group.uuid,
                label: (
                    <div className='flex items-center gap-3'>
                        <TemplateIconDisplay
                            icon={group.icon}
                            className='h-5 w-5 text-muted-foreground'
                        />
                        <div className='flex flex-col text-left'>
                            <span className='block truncate font-medium'>
                                {group.name}
                            </span>
                            {group.description && (
                                <span className='block truncate text-xs text-muted-foreground'>
                                    {group.description}
                                </span>
                            )}
                        </div>
                    </div>
                ),
            })) || [],
        [templateGroups]
    )

    const templateItems = useMemo(() => {
        const group = templateGroups?.find(
            g => g.uuid === selectedTemplateGroupUuid
        )
        return (
            group?.templates?.map(template => ({
                value: template.uuid,
                label: (
                    <div className='flex items-center gap-3'>
                        <TemplateIconDisplay
                            icon={group.icon}
                            className='h-5 w-5 text-muted-foreground'
                        />
                        <div className='flex flex-col text-left'>
                            <span className='block truncate font-medium'>
                                {template.name}
                            </span>
                            {template.description && (
                                <span className='block truncate text-xs text-muted-foreground'>
                                    {template.description}
                                </span>
                            )}
                        </div>
                    </div>
                ),
            })) || []
        )
    }, [templateGroups, selectedTemplateGroupUuid])

    const onSubmit = async (values: FormData) => {
        try {
            await reinstallServer(server.uuid, {
                templateUuid: values.templateUuid,
                accountPassword: values.accountPassword,
                startOnCompletion: true,
            })
            toast.success('Operating system installation started successfully.')
            mutate()
        } catch (error) {
            console.error(error)
            toast.error('Failed to start installation.')
        }
    }

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className='space-y-4'>
                <SelectForm
                    name='templateGroupUuid'
                    label='Pick your operating system'
                    placeholder='Select an OS family'
                    items={templateGroupItems}
                />

                <SelectForm
                    name='templateUuid'
                    label='Pick which version'
                    placeholder='Select a version'
                    items={templateItems}
                    disabled={!selectedTemplateGroupUuid}
                />

                <InputForm
                    name='accountPassword'
                    label='Root/Administrator Password'
                    type='password'
                />

                <FormButton className='w-full'>
                    Install Operating System
                </FormButton>
            </form>
        </Form>
    )
}
