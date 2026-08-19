import {
    createServerPreset,
    serverPresetQueries,
    serverPresetSchema,
} from '@/features/servers/presets/api.ts'
import {
    describePresetSettings,
    presetSettingsFromForm,
} from '@/features/servers/presets/form-mapping.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import type { ServerPreset, ServerPresetSettings } from '@/types/server-preset'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconBookmark } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm, useFormContext } from 'react-hook-form'
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
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

/**
 * Saves the create form as it currently stands, so the next identical build is
 * one pick instead of fifteen fields.
 *
 * The create form is *not* validated first, on purpose: a preset is allowed to
 * be a partial answer, and demanding a name and hostname before you can save
 * the shape of a build would defeat the point.
 */
const SavePresetDialog = () => {
    const [open, setOpen] = useState(false)
    const { getValues } = useFormContext()
    const mutate = useQueryMutator<ServerPreset[]>(
        serverPresetQueries.list().queryKey
    )

    const form = useForm({
        resolver: zodResolver(serverPresetSchema),
        defaultValues: { name: '', description: '' },
    })

    // Read once per open so the summary under the fields cannot drift from what
    // the button will actually save.
    const settings = open
        ? (presetSettingsFromForm(getValues()) as ServerPresetSettings)
        : null

    const submit = async (data: z.infer<typeof serverPresetSchema>) => {
        try {
            const preset = await createServerPreset(
                data,
                presetSettingsFromForm(getValues())
            )

            await mutate(presets => [...(presets ?? []), preset], false)

            toast.add({ title: 'Preset saved', type: 'success' })

            form.reset()
            setOpen(false)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save preset', type: 'error' })
            console.error(e)
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button type={'button'} variant={'outline'} size={'sm'}>
                        <IconBookmark className={'size-4'} /> Save as preset
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Save as preset
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Everything except the name, hostname, VM ID, owner and
                        password is remembered.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-3'}>
                            <InputForm name={'name'} label={'Name'} />
                            <InputForm
                                name={'description'}
                                label={'Description'}
                                description={'Optional.'}
                            />
                            {settings && (
                                <p className={'text-muted-foreground text-xs'}>
                                    Saving: {describePresetSettings(settings)}
                                </p>
                            )}
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save preset</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default SavePresetDialog
