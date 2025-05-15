import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import { useShallow } from 'zustand/react/shallow'

import { storageSchema } from '@/api/admin/nodes/storages/createStorage.ts'
import updateStorage from '@/api/admin/nodes/storages/updateStorage.ts'
import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr.ts'

import useStoragesModalStore from '@/components/interfaces/Admin/Node/Storages/use-storages-modal-store.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import CheckboxItemForm from '@/components/ui/Forms/CheckboxItemForm.tsx'

const EditStorageModal = () => {
    const { mutate } = useStoragesSWR()

    const { nodeId } = StorageRoute.useParams()
    const [storage, open, close] = useStoragesModalStore(
        useShallow(state => [
            state.modalData,
            state.activeModal === 'edit',
            state.closeModal,
        ])
    )

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

    useEffect(() => {
        if (!storage) return

        form.reset({
            displayName: storage.displayName ?? '',
            description: storage.description ?? '',
            name: storage.name,
            size: (storage.size / 1024 / 1024).toString(),
            isShareable: storage.isShareable,
            storesKvm: storage.storesKvm,
            storesLxc: storage.storesLxc,
            storesLxcTemplates: storage.storesLxcTemplates,
            storesBackups: storage.storesBackups,
            storesIso: storage.storesIso,
            storesSnippets: storage.storesSnippets,
        })
    }, [storage])

    const submit = async ({ size, ...data }: z.infer<typeof storageSchema>) => {
        try {
            const updatedStorage = await updateStorage(nodeId, storage!.id, {
                size: size * 1024 * 1024,
                ...data,
            })

            await mutate(data => {
                if (!data) return

                return data.map(item => {
                    if (item.id === updatedStorage.id) {
                        return updatedStorage
                    }
                    return item
                })
            }, false)

            close('edit')

            toast.success('Storage updated')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={open => !open && close('edit')}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>
                        Editing {storage?.displayName ?? storage?.name}
                    </CredenzaTitle>
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
                            <FormButton>Save</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default EditStorageModal
