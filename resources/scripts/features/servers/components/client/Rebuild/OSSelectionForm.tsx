import {
    reinstallServer,
    serverQueries,
    useTemplateGroups,
} from '@/features/servers/detail/api.ts'
import TemplateIconDisplay from '@/features/template-groups/components/TemplateIconDisplay'
import { Server } from '@/types/server'
import { handleFormErrors } from '@/utils/http'
import { zodResolver } from '@hookform/resolvers/zod'
import { useQueryClient } from '@tanstack/react-query'
import { useEffect, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import {
    Form,
    FormButton,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import InputForm from '@/components/ui/Forms/InputForm'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import { toast } from '@/components/ui/Toast'

interface OSSelectionFormProps {
    server: Server
}

const formSchema = z.object({
    templateGroupUuid: z.string().min(1, 'Please select an OS family'),
    templateUuid: z.string().min(1, 'Please select a version'),
    accountPassword: z
        .string()
        .min(8, 'Password must be at least 8 characters'),
})

type FormData = z.infer<typeof formSchema>

export default function OSSelectionForm({ server }: OSSelectionFormProps) {
    const queryClient = useQueryClient()
    const { data: templateGroups } = useTemplateGroups(server.uuid)

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

    const selectedGroup = useMemo(
        () => templateGroups?.find(g => g.uuid === selectedTemplateGroupUuid),
        [templateGroups, selectedTemplateGroupUuid]
    )

    const templateGroupItems = (templateGroups ?? []).map(group => ({
        value: group.uuid,
        label: (
            <div className='flex items-center gap-3 pr-4'>
                <TemplateIconDisplay
                    icon={group.icon}
                    className='text-muted-foreground size-5'
                />
                <div className='flex flex-col text-left'>
                    <span className='block truncate font-medium'>
                        {group.name}
                    </span>
                    {group.description && (
                        <span className='text-muted-foreground block truncate text-xs'>
                            {group.description}
                        </span>
                    )}
                </div>
            </div>
        ),
    }))
    const templateItems = (selectedGroup?.templates ?? []).map(template => ({
        value: template.uuid,
        label: (
            <div className='flex items-center gap-3 pr-4'>
                <TemplateIconDisplay
                    icon={selectedGroup?.icon ?? null}
                    className='text-muted-foreground size-5'
                />
                <div className='flex flex-col text-left'>
                    <span className='block truncate font-medium'>
                        {template.name}
                    </span>
                    {template.description && (
                        <span className='text-muted-foreground block truncate text-xs'>
                            {template.description}
                        </span>
                    )}
                </div>
            </div>
        ),
    }))

    const onSubmit = async (values: FormData) => {
        try {
            await reinstallServer(server.uuid, {
                templateUuid: values.templateUuid,
                accountPassword: values.accountPassword,
                startOnCompletion: true,
            })
            toast.add({
                title: 'The installation started successfully.',
                type: 'success',
            })
            queryClient.invalidateQueries({
                queryKey: serverQueries.detail(server.uuid).queryKey,
            })
        } catch (error) {
            if (handleFormErrors(error, form.setError)) return

            toast.add({ title: 'Failed to start installation.', type: 'error' })
        }
    }

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className='space-y-4'>
                <FormField
                    control={form.control}
                    name='templateGroupUuid'
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Pick your operating system</FormLabel>
                            <Select
                                items={templateGroupItems}
                                onValueChange={field.onChange}
                                defaultValue={field.value}
                            >
                                <FormControl>
                                    <SelectTrigger className='h-auto! w-full text-left *:data-[slot=select-value]:line-clamp-none!'>
                                        <SelectValue placeholder='Select an OS family' />
                                    </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                    {templateGroupItems.map(item => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <FormMessage />
                        </FormItem>
                    )}
                />

                <FormField
                    control={form.control}
                    name='templateUuid'
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Pick which version</FormLabel>
                            <Select
                                items={templateItems}
                                onValueChange={field.onChange}
                                value={field.value}
                                disabled={!selectedTemplateGroupUuid}
                            >
                                <FormControl>
                                    <SelectTrigger className='h-auto! w-full text-left *:data-[slot=select-value]:line-clamp-none!'>
                                        <SelectValue placeholder='Select a version' />
                                    </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                    {templateItems.map(item => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <FormMessage />
                        </FormItem>
                    )}
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
