import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import Modal from '@/components/elements/Modal'
import { NodeContext } from '@/state/admin/node'
import { useFlashKey } from '@/util/useFlash'
import createIso from '@/api/admin/nodes/isos/createIso'
import QueryFileButton from '@/components/admin/nodes/isos/QueryFileButton'
import { FileMetadata } from '@/api/admin/tools/queryRemoteFile'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { FormProvider, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useCallback, useMemo, useState } from 'react'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import { KeyedMutator } from 'swr'
import { IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import { data } from 'autoprefixer'
import SelectForm from '@/components/elements/forms/SelectForm'

interface Props {
    open: boolean
    onClose: () => void
    mutate: KeyedMutator<IsoResponse>
}

const CreateIsoModal = ({ open, onClose, mutate }: Props) => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`admin.nodes.${nodeId}.isos.create`)
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.nodes.isos')

    const schemaWithoutDownloading = z.object({
        name: z.string().max(40).nonempty(),
        fileName: z
            .string()
            .regex(/\.iso$/, t('non_iso_file_name_error') ?? 'The file extension must end in .iso')
            .max(191)
            .nonempty(),
    })

    const schemaWithoutChecksum = z.object({
        name: z.string().max(40).nonempty(),
        link: z.string().max(191).nonempty(),
        fileName: z
            .string()
            .regex(/\.iso$/, t('non_iso_file_name_error') ?? 'The file extension must end in .iso')
            .max(191)
            .nonempty(),
        checksumAlgorithm: z.literal('none'),
        checksum: z.string(),
        hidden: z.boolean(),
    })

    const schemaWithChecksum = z.object({
        name: z.string().max(40).nonempty(),
        link: z.string().max(191).nonempty(),
        fileName: z
            .string()
            .regex(/\.iso$/, t('non_iso_file_name_error') ?? 'The file extension must end in .iso')
            .max(191)
            .nonempty(),
        checksumAlgorithm: z.enum(['md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512']),
        checksum: z.string().max(191).nonempty(),
        hidden: z.boolean(),
    })

    const schema = z.discriminatedUnion('checksumAlgorithm', [schemaWithoutChecksum, schemaWithChecksum])

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            link: '',
            fileName: '',
            checksumAlgorithm: 'none',
            checksum: '',
            hidden: false,
        },
    })

    const watchChecksumAlgorithm = form.watch('checksumAlgorithm')

    const submit = async (_data: any) => {
        const { checksumAlgorithm, ...data } = _data as z.infer<typeof schema>
        clearFlashes()

        try {
            const iso = await createIso(nodeId, {
                checksumAlgorithm: checksumAlgorithm !== 'none' ? checksumAlgorithm : undefined,
                ...data,
            })

            mutate(isoResponse => {
                if (!isoResponse) return isoResponse
                if (isoResponse.pagination.totalPages > 1) return isoResponse

                return {
                    ...isoResponse,
                    items: [...isoResponse.items, iso],
                }
            }, false)

            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const checksumAlgorithms = [
        { value: 'none', label: 'None' },
        { value: 'md5', label: 'MD5' },
        { value: 'sha1', label: 'SHA1' },
        { value: 'sha224', label: 'SHA224' },
        { value: 'sha256', label: 'SHA256' },
        { value: 'sha384', label: 'SHA384' },
        { value: 'sha512', label: 'SHA512' },
    ]

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{t('create_modal.title')}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={`admin.nodes.${nodeId}.isos.create`} />

                        <TextInputForm name='name' label={tStrings('display_name')} />
                        <div className={`grid grid-cols-3 sm:grid-cols-4 gap-3 items-start`}>
                            <TextInputForm className='col-span-2 sm:col-span-3' name='link' label={tStrings('link')} />
                            <QueryFileButton />
                        </div>
                        <TextInputForm name='fileName' label={tStrings('file_name')} />
                        <SelectForm
                            data={checksumAlgorithms}
                            name='checksumAlgorithm'
                            label={t('checksum_algorithm')}
                        />
                        <TextInputForm
                            disabled={watchChecksumAlgorithm === 'none'}
                            name='checksum'
                            label={t('checksum')}
                        />
                        <CheckboxForm className='mt-3' name='hidden' label={tStrings('hidden')} />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.formState.isSubmitting}>
                            {tStrings('create')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default CreateIsoModal
