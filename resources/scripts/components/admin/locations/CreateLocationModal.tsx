import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import createLocation from '@/api/admin/locations/createLocation'
import { LocationResponse } from '@/api/admin/locations/getLocations'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import TextareaForm from '@/components/elements/forms/TextareaForm'


interface Props {
    mutate: KeyedMutator<LocationResponse>
    open: boolean
    onClose: () => void
}

const CreateLocationModal = ({ mutate, open, onClose }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin.locations.create'
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
            shortCode: '',
            description: '',
        },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()

        try {
            const location = await createLocation(
                data.shortCode,
                data.description
            )

            mutate(data => {
                if (!data || data.pagination.currentPage !== 1) return data

                return {
                    ...data,
                    items: [location, ...data.items],
                }
            }, false)

            form.reset()

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
                <Modal.Title>{t('create_location')}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={'admin.locations.create'}
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
                            {tStrings('create')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default CreateLocationModal