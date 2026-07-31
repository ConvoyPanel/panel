import useStoragesModalStore from '@/features/nodes/hooks/use-storages-modal-store.ts'
import {
    storageQueries,
    storageSchema,
    updateStorage,
} from '@/features/nodes/storages/api.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import CheckboxItemForm from '@/components/ui/Forms/CheckboxItemForm.tsx'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const EditStorageModal = () => {
    const { nodeId } = StorageRoute.useParams()
    const mutate = useQueryMutator<NodeStorage[]>(
        storageQueries.all(Number(nodeId))
    )
    const {
        open,
        data: storage,
        close,
    } = useModal(useStoragesModalStore, 'edit')

    const form = useForm({
        resolver: zodResolver(storageSchema),
        defaultValues: {
            displayName: '',
            description: '',
            name: '',
            size: '',
            reservedBytes: '',
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
            reservedBytes:
                storage.reservedBytes != null
                    ? (storage.reservedBytes / 1024 / 1024).toString()
                    : '',
            isShareable: storage.isShareable,
            storesKvm: storage.storesKvm,
            storesLxc: storage.storesLxc,
            storesLxcTemplates: storage.storesLxcTemplates,
            storesBackups: storage.storesBackups,
            storesIso: storage.storesIso,
            storesSnippets: storage.storesSnippets,
        })
    }, [storage])

    const submit = async ({
        size,
        reservedBytes,
        ...data
    }: z.infer<typeof storageSchema>) => {
        if (!storage) return

        try {
            const updatedStorage = await updateStorage(
                Number(nodeId),
                storage.id,
                {
                    size: size * 1024 * 1024,
                    reservedBytes: reservedBytes
                        ? reservedBytes * 1024 * 1024
                        : null,
                    ...data,
                }
            )

            await mutate(data => {
                if (!data) return

                return data.map(item => {
                    if (item.id === updatedStorage.id) {
                        return updatedStorage
                    }
                    return item
                })
            }, false)

            close()

            toast.add({ title: 'Storage updated', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Editing {storage?.displayName ?? storage?.name}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody className={'space-y-2'}>
                            <InputForm
                                name={'displayName'}
                                label={'Display Name'}
                            />
                            <InputForm
                                name={'description'}
                                label={'Description'}
                            />
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                autoComplete={'off'}
                            />
                            <InputForm name={'size'} label={'Size (MiB)'} />
                            <InputForm
                                name={'reservedBytes'}
                                label={'Reserved headroom (MiB)'}
                                description={
                                    'Free space Convoy will never allocate into. Leave blank for none.'
                                }
                            />
                            <div>
                                <h3 className={'text-sm font-semibold'}>
                                    Content Types
                                </h3>
                                <p
                                    className={
                                        'text-muted-foreground text-[0.8rem]'
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
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditStorageModal
