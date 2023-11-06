import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import { Location, LocationResponse } from '@/api/admin/locations/getLocations'
import updateLocation from '@/api/admin/locations/updateLocation'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import TextareaForm from '@/components/elements/forms/TextareaForm'


interface Props {
    location: Location
    open: boolean
    onClose: () => void
    mutate: KeyedMutator<LocationResponse>
}

const EditLocationModal = ({ location, open, onClose, mutate }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.locations.${location.id}.edit`
    )
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.locations')

    const schema = z.object({
        shortCode: z.string().nonempty().max(60),
        description: z.string().max(191).nullable(),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            shortCode: location.shortCode,
            description: location.description,
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            const updatedLocation = await updateLocation(
                location.id,
                data.shortCode,
                data.description
            )

            mutate(data => {
                if (!data) return data

                return {
                    ...data,
                    items: data.items.map(l =>
                        l.id === location.id ? updatedLocation : l
                    ),
                }
            }, false)

            form.reset({
                shortCode: updatedLocation.shortCode,
                description: updatedLocation.description,
            })

            onClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    const handleClose = () => {
        clearFlashes()
        form.reset()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>
                    {tStrings('edit')} {location.shortCode}
                </Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={`admin.locations.${location.id}.edit`}
                        />
                        <TextInputForm
                            name='shortCode'
                            label={t('short_code')}
                        />
                        <TextareaForm
                            name='description'
                            label={tStrings('description')}
                        />
                    </Modal.Body>
                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action
                            type='submit'
                            loading={form.formState.isSubmitting}
                        >
                            {tStrings('save')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default EditLocationModal