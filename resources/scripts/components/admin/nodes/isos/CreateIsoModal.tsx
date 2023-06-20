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
import { ISO, IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import { data } from 'autoprefixer'
import SelectForm from '@/components/elements/forms/SelectForm'
import SegmentedControl from '@/components/elements/SegmentedControl'

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
        shouldDownload: z.literal(false),
        name: z.string().max(40).nonempty(),
        fileName: z
            .string()
            .regex(/\.iso$/, t('create_modal.non_iso_file_name_error') ?? 'The file extension must end in .iso')
            .max(191)
            .nonempty(),
        hidden: z.boolean(),
    })

    const schemaWithoutChecksum = z.object({
        shouldDownload: z.literal(true),
        name: z.string().max(40).nonempty(),
        link: z.string().url().max(191).nonempty(),
        fileName: z
            .string()
            .regex(/\.iso$/, t('create_modal.non_iso_file_name_error') ?? 'The file extension must end in .iso')
            .max(191)
            .nonempty(),
        checksumAlgorithm: z.literal('none'),
        checksum: z.string(),
        hidden: z.boolean(),
    })

    const schemaWithChecksum = z.object({
        shouldDownload: z.literal(true),
        name: z.string().max(40).nonempty(),
        link: z.string().url().max(191).nonempty(),
        fileName: z
            .string()
            .regex(/\.iso$/, t('create_modal.non_iso_file_name_error') ?? 'The file extension must end in .iso')
            .max(191)
            .nonempty(),
        checksumAlgorithm: z.enum(['md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512']),
        checksum: z.string().max(191).nonempty(),
        hidden: z.boolean(),
    })

    const schemaWithDownloading = z.discriminatedUnion('checksumAlgorithm', [schemaWithoutChecksum, schemaWithChecksum])

    const schema = z.union([schemaWithoutDownloading, schemaWithoutChecksum, schemaWithChecksum])

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            shouldDownload: true,
            name: '',
            link: '',
            fileName: '',
            checksumAlgorithm: 'none',
            checksum: '',
            hidden: false,
        },
    })

    const watchChecksumAlgorithm = form.watch('checksumAlgorithm')
    const watchShouldDownload: boolean = form.watch('shouldDownload')

    const submit = async (_data: any) => {
        clearFlashes()

        const data = _data as z.infer<typeof schema>

        if (data.shouldDownload) {
            const { checksumAlgorithm, ...params } = data

            try {
                const iso = await createIso(nodeId, {
                    checksumAlgorithm: checksumAlgorithm !== 'none' ? checksumAlgorithm : undefined,
                    ...params,
                })

                appendToSWR(iso)
            } catch (e) {
                clearAndAddHttpError(e as Error)

                return
            }
        }

        if (!data.shouldDownload) {
            try {
                const iso = await createIso(nodeId, data)

                appendToSWR(iso)
            } catch (e) {
                clearAndAddHttpError(e as Error)

                return
            }
        }

        handleClose()
    }

    const appendToSWR = (iso: ISO) => {
        mutate(isoResponse => {
            if (!isoResponse) return isoResponse
            if (
                isoResponse.pagination.totalPages > 1 &&
                isoResponse.pagination.currentPage !== isoResponse.pagination.totalPages
            )
                return isoResponse

            return {
                ...isoResponse,
                items: [...isoResponse.items, iso],
            }
        }, false)
    }

    const handleClose = () => {
        form.reset()
        clearFlashes()
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
                        <SegmentedControl
                            className='!w-full'
                            disabled={form.formState.isSubmitting}
                            value={watchShouldDownload ? 'new' : 'import'}
                            onChange={val => form.setValue('shouldDownload', val === 'new')}
                            data={[
                                { value: 'new', label: tStrings('new') },
                                { value: 'import', label: tStrings('import') },
                            ]}
                        />
                        <TextInputForm name='name' label={tStrings('display_name')} className={'mt-3'} />
                        {watchShouldDownload && (
                            <div className={`grid grid-cols-3 sm:grid-cols-4 gap-3 items-start`}>
                                <TextInputForm
                                    className='col-span-2 sm:col-span-3'
                                    name='link'
                                    label={tStrings('link')}
                                />
                                <QueryFileButton />
                            </div>
                        )}
                        <TextInputForm name='fileName' label={tStrings('file_name')} />
                        {watchShouldDownload && (
                            <>
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
                            </>
                        )}
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
