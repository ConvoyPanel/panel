import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { nodeSchema } from '@/api/admin/nodes/createNode.ts'

import FullscreenLayout from '@/components/layouts/FullscreenLayout.tsx'

import ConnectionSettingsForm from '@/components/interfaces/Admin/Node/Create/ConnectionSettingsForm.tsx'
import GeneralSettingsForm from '@/components/interfaces/Admin/Node/Create/GeneralSettingsForm.tsx'

import { Form, FormButton } from '@/components/ui/Form'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute(
    '/_app/admin/(fullscreen)/nodes/create'
)({
    component: CreateNodePage,
})

function CreateNodePage() {
    const form = useForm({
        resolver: zodResolver(nodeSchema),
        defaultValues: {
            displayName: '',
            locationId: '',
            name: '',
            verifyTls: true,
            tokenId: '',
            tokenSecret: '',
            rootPrivileges: false,
            privilegeSeparationDisabled: false,
            fqdn: '',
            port: '8006',
        },
    })

    const submit = async (_data: z.infer<typeof nodeSchema>) => {}

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit as any)}>
                <FullscreenLayout
                    backLink={'/admin/nodes'}
                    title={'Add a new node'}
                    headerActions={
                        <FormButton size={'sm'} className={'flex'}>
                            Add node <IconCheck className={'ml-2 size-4'} />
                        </FormButton>
                    }
                    center
                >
                    <div className={'flex w-full max-w-lg flex-col space-y-16'}>
                        <GeneralSettingsForm />
                        <ConnectionSettingsForm />
                        <div className={'flex flex-col space-y-4'}>
                            <Heading as={'h3'}>Specifications</Heading>
                        </div>
                    </div>
                </FullscreenLayout>
            </form>
        </Form>
    )
}
