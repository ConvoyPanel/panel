import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { nodeSchema } from '@/api/admin/nodes/createNode.ts'

import FullscreenLayout from '@/components/layouts/FullscreenLayout.tsx'

import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/Tabs'

export const Route = createLazyFileRoute(
    '/_app/admin/(fullscreen)/nodes/create'
)({
    component: CreateNodePage,
})

function CreateNodePage() {
    const form = useForm({
        resolver: zodResolver(nodeSchema),
        defaultValues: {
            name: '',
            locationId: '',
            cluster: '',
            verifyTls: true,
            tokenId: '',
            secret: '',
            fqdn: '',
            port: '',
        },
    })

    const submit = async (data: z.infer<typeof nodeSchema>) => {}

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
                    <Tabs
                        defaultValue={'general'}
                        className={
                            'mt-3 flex w-full max-w-lg flex-col items-center'
                        }
                    >
                        <TabsList>
                            <TabsTrigger value={'general'}>General</TabsTrigger>
                            <TabsTrigger value={'connection'}>
                                Connection
                            </TabsTrigger>
                            <TabsTrigger value={'specifications'}>
                                Specifications
                            </TabsTrigger>
                        </TabsList>
                        <TabsContent value={'general'} className={'w-full'}>
                            <InputForm name={'name'} label={'Display Name'} />
                        </TabsContent>
                    </Tabs>
                </FullscreenLayout>
            </form>
        </Form>
    )
}
