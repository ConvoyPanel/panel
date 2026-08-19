import {
    serverPresetSchema,
    updateServerPreset,
} from '@/features/servers/presets/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import { useServerPresetsModalStore } from '@/routes/_app/admin/_dashboard/server-presets.lazy.tsx'
import { Mutator } from '@/types/query.ts'
import type { ServerPreset } from '@/types/server-preset'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    mutate: Mutator<ServerPreset[]>
}

/**
 * Renames a preset. Its settings are not edited here — they are captured from
 * the server-create form, which is the only screen that can tell a workable
 * build from an unworkable one.
 */
const EditPresetModal = ({ mutate }: Props) => {
    const {
        open,
        data: preset,
        close,
    } = useModal(useServerPresetsModalStore, 'edit')

    const form = useForm({
        resolver: zodResolver(serverPresetSchema),
        defaultValues: { name: '', description: '' },
    })

    const { reset } = form
    useEffect(() => {
        if (preset) {
            reset({
                name: preset.name,
                description: preset.description ?? '',
            })
        }
    }, [preset, reset])

    const submit = async (data: z.infer<typeof serverPresetSchema>) => {
        if (!preset) return

        try {
            // The settings ride along unchanged: the endpoint replaces the whole
            // record, so omitting them would silently empty the preset.
            const updated = await updateServerPreset(
                preset.uuid,
                data,
                preset.settings
            )

            await mutate(
                presets =>
                    presets?.map(item =>
                        item.uuid === updated.uuid ? updated : item
                    ),
                false
            )

            toast.add({ title: 'Preset updated', type: 'success' })

            close()
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
            console.error(e)
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Edit preset</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        The saved settings are unchanged — re-save from the
                        create form to change those.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-3'}>
                            <InputForm name={'name'} label={'Name'} />
                            <InputForm
                                name={'description'}
                                label={'Description'}
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save changes</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditPresetModal
