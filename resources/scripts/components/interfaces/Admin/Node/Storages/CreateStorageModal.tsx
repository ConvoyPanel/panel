import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { NodeStorage } from '@/types/storage.ts'

import {
    createStorage,
    storageSchema,
    storageQueries,
} from '@/features/nodes/storages/api.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import CheckboxItemForm from '@/components/ui/Forms/CheckboxItemForm.tsx'

const CreateStorageModal = () => {
    const { nodeId } = StorageRoute.useParams()
    const mutate = useQueryMutator<NodeStorage[]>(
        storageQueries.all(Number(nodeId))
    )
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(storageSchema),
        defaultValues: {
            displayName: '',
            description: '',
            name: '',
            size: '',
            isShareable: false,
            storesKvm: false,
            storesLxc: false,
            storesLxcTemplates: false,
            storesBackups: false,
            storesIso: false,
            storesSnippets: false,
        },
    })

    const submit = async ({ size, ...data }: z.infer<typeof storageSchema>) => {
        try {
            const storage = await createStorage(Number(nodeId), {
                size: size * 1024 * 1024,
                ...data,
            })

            await mutate(data => {
                if (!data) return

                return data.concat(storage)
            }, false)

            form.reset()
            setOpen(false)
            toast.success('Storage created')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button className={'flex'} size={'sm'}>
                    <IconPlus className={'mr-2 size-4'} /> Add storage
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>New Storage</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <CredenzaBody className={'space-y-2'}>
                            <InputForm
                                name={'displayName'}
                                label={'Display Name'}
                            />
                            <InputForm
                                name={'description'}
                                label={'Description'}
                            />
                            <InputForm name={'name'} label={'Name'} autoComplete={'off'} />
                            <InputForm name={'size'} label={'Size (MiB)'} />
                            <div>
                                <h3 className={'text-sm font-semibold'}>
                                    Content Types
                                </h3>
                                <p
                                    className={
                                        'text-[0.8rem] text-muted-foreground'
                                    }
                                >
                                    Select which content types this storage
                                    should be able to store.
                                </p>
                                <ul className={'mt-2 space-y-2'}>
                                    <li>
                                        <CheckboxItemForm
                                            name={'storesKvm'}
                                            label={'KVM'}
                                        />
                                    </li>
                                    <li>
                                        <CheckboxItemForm
                                            name={'storesLxc'}
                                            label={'LXC'}
                                        />
                                    </li>
                                    <li>
                                        <CheckboxItemForm
                                            name={'storesLxcTemplates'}
                                            label={'LXC Templates'}
                                        />
                                    </li>
                                    <li>
                                        <CheckboxItemForm
                                            name={'storesBackups'}
                                            label={'Backups'}
                                        />
                                    </li>
                                    <li>
                                        <CheckboxItemForm
                                            name={'storesIso'}
                                            label={'ISO Images'}
                                        />
                                    </li>
                                    <li>
                                        <CheckboxItemForm
                                            name={'storesSnippets'}
                                            label={'Snippets'}
                                        />
                                    </li>
                                </ul>
                            </div>
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Add storage</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateStorageModal
