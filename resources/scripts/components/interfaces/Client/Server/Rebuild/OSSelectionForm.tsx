import { Server } from '@/types/server'
import { handleFormErrors } from '@/utils/http'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import reinstallServer from '@/api/servers/reinstallServer'
import useServerSWR from '@/api/servers/use-server-swr'
import useTemplateGroupsSWR from '@/api/servers/use-template-groups-swr'

import TemplateIconDisplay from '@/components/interfaces/Admin/Template/TemplateIconDisplay'

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

    const selectedGroup = useMemo(
        () => templateGroups?.find(g => g.uuid === selectedTemplateGroupUuid),
        [templateGroups, selectedTemplateGroupUuid]
    )

    const onSubmit = async (values: FormData) => {
        try {
            await reinstallServer(server.uuid, {
                templateUuid: values.templateUuid,
                accountPassword: values.accountPassword,
                startOnCompletion: true,
            })
            toast.success('The installation started successfully.')
            mutate()
        } catch (error) {
            if (handleFormErrors(error, form.setError)) return

            toast.error('Failed to start installation.')
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
                                onValueChange={field.onChange}
                                defaultValue={field.value}
                            >
                                <FormControl>
                                    <SelectTrigger className='h-auto text-left [&>span]:line-clamp-none'>
                                        <SelectValue placeholder='Select an OS family' />
                                    </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                    {templateGroups?.map(group => (
                                        <SelectItem
                                            key={group.uuid}
                                            value={group.uuid}
                                        >
                                            <div className='flex items-center gap-3 pr-4'>
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
                                onValueChange={field.onChange}
                                value={field.value}
                                disabled={!selectedTemplateGroupUuid}
                            >
                                <FormControl>
                                    <SelectTrigger className='h-auto text-left [&>span]:line-clamp-none'>
                                        <SelectValue placeholder='Select a version' />
                                    </SelectTrigger>
                                </FormControl>
                                <SelectContent>
                                    {selectedGroup?.templates?.map(template => (
                                        <SelectItem
                                            key={template.uuid}
                                            value={template.uuid}
                                        >
                                            <div className='flex items-center gap-3 pr-4'>
                                                <TemplateIconDisplay
                                                    icon={selectedGroup.icon}
                                                    className='h-5 w-5 text-muted-foreground'
                                                />
                                                <div className='flex flex-col text-left'>
                                                    <span className='block truncate font-medium'>
                                                        {template.name}
                                                    </span>
                                                    {template.description && (
                                                        <span className='block truncate text-xs text-muted-foreground'>
                                                            {
                                                                template.description
                                                            }
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
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
