import { Location, LocationResponse } from '@/api/admin/locations/getLocations'
import Modal from '@/components/elements/Modal'
import { useFormik } from 'formik'
import * as yup from 'yup'
import TextInput from '@/components/elements/inputs/TextInput'
import Textarea from '@/components/elements/inputs/Textarea'
import updateLocation from '@/api/admin/locations/updateLocation'
import { KeyedMutator } from 'swr'
import createLocation from '@/api/admin/locations/createLocation'
import useFlash from '@/util/useFlash'

interface Props {
    location?: Location
    open: boolean
    onClose: () => void
    mutate: KeyedMutator<LocationResponse>
}

const EditLocationModal = ({ location, open, onClose, mutate }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const form = useFormik({
        initialValues: {
            shortCode: location?.shortCode ?? '',
            description: location?.description ?? '',
        },
        validationSchema: yup.object({
            shortCode: yup.string().required('Specify a short code').max(60, 'Please limit up to 60 characters'),
            description: yup.string().max(191, 'Please limit up to 191 characters'),
        }),
        onSubmit: ({ shortCode, description }, { setSubmitting}) => {
            setSubmitting(true)
            clearFlashes('admin:locations')

            if (location) {
                updateLocation(location.id, shortCode, description as string|undefined)
                    .then(() => {
                        handleClose()
                        // mutate the updated location in the data

                        // @ts-expect-error
                        mutate(({items, ...data}) => ({
                            // @ts-expect-error
                            items: items.map(l => l.id === location.id ? { ...l, shortCode, description } : l),
                            ...data
                        }), false)
                    }).catch((error) => {
                        clearAndAddHttpError({ key: 'admin:locations', error })
                        setSubmitting(false)
                      })
            } else {
                createLocation(shortCode, description as string|undefined)
                    .then((location) => {
                        handleClose()
                        // mutate the new location in the data
                        // @ts-expect-error
                        mutate(({items, ...data}: LocationResponse|undefined) => ({
                            items: [location].concat(items),
                            ...data
                        }), false)
                    }).catch((error) => {
                        clearAndAddHttpError({ key: 'admin:locations', error })
                        setSubmitting(false)
                      })
            }
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{location ? 'Edit' : 'New'} Location</Modal.Title>
            </Modal.Header>

            <form onSubmit={form.handleSubmit}>
                <Modal.Body>
                    <TextInput
                        value={form.values.shortCode}
                        onChange={form.handleChange}
                        error={form.touched.shortCode ? form.errors.shortCode : undefined}
                        name='shortCode'
                        placeholder='Name'
                    />
                    <Textarea
                        value={form.values.description}
                        onChange={form.handleChange}
                        error={form.touched.description ? form.errors.description : undefined}
                        name='description'
                        placeholder='Description'
                        wrapperClassName='mt-3'
                    />
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={handleClose}>Cancel</Modal.Action>
                    <Modal.Action type='submit' loading={form.isSubmitting}>
                        {location ? 'Update' : 'Create'}
                    </Modal.Action>
                </Modal.Actions>
            </form>
        </Modal>
    )
}

export default EditLocationModal
