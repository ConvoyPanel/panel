import { useFlashKey } from '@/util/useFlash'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'

import getTelemetryReport from '@/api/admin/telemetry/getTelemetryReport'
import updateTelemetrySettings from '@/api/admin/telemetry/updateTelemetrySettings'
import useTelemetrySettingsSWR from '@/api/admin/telemetry/useTelemetrySettingsSWR'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import Modal from '@/components/elements/Modal'
import Spinner from '@/components/elements/Spinner'
import Switch from '@/components/elements/inputs/Switch'

const FLASH_KEY = 'admin:settings:telemetry'

const TelemetryCard = () => {
    const { t } = useTranslation('admin.settings')
    const { t: tStrings } = useTranslation('strings')
    const { data: settings, mutate } = useTelemetrySettingsSWR()
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(FLASH_KEY)
    const [enabled, setEnabled] = useState(false)
    const [saving, setSaving] = useState(false)
    const [reportOpen, setReportOpen] = useState(false)
    const [report, setReport] = useState<Record<string, unknown> | null>(null)

    useEffect(() => {
        if (settings) setEnabled(settings.enabled)
    }, [settings])

    const lockedOff = settings?.disabledBy != null

    const save = async () => {
        clearFlashes()
        setSaving(true)
        try {
            await mutate(await updateTelemetrySettings(enabled), {
                revalidate: false,
            })
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
        setSaving(false)
    }

    const openReport = async () => {
        clearFlashes()
        setReport(null)
        setReportOpen(true)
        try {
            setReport(await getTelemetryReport())
        } catch (error) {
            setReportOpen(false)
            clearAndAddHttpError(error as any)
        }
    }

    return (
        <>
            <Modal open={reportOpen} onClose={() => setReportOpen(false)}>
                <Modal.Header>
                    <Modal.Title>{t('telemetry.report_title')}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Modal.Description bottomMargin>
                        {t('telemetry.report_description')}
                    </Modal.Description>
                    {report ? (
                        <pre className='max-h-[60vh] overflow-auto rounded bg-background p-3 font-mono text-xs text-foreground'>
                            {JSON.stringify(report, null, 4)}
                        </pre>
                    ) : (
                        <Spinner />
                    )}
                </Modal.Body>
                <Modal.Actions>
                    <Modal.Action onClick={() => setReportOpen(false)}>
                        {t('telemetry.close')}
                    </Modal.Action>
                </Modal.Actions>
            </Modal>
            <FormCard className='w-full'>
                <FormCard.Body>
                    <FormCard.Title>{t('telemetry.title')}</FormCard.Title>
                    <div className='space-y-3 mt-3'>
                        <FlashMessageRender byKey={FLASH_KEY} />
                        <p className='description-small !text-foreground'>
                            {t('telemetry.description')}
                        </p>
                        {settings ? (
                            <>
                                <Switch
                                    checked={enabled}
                                    disabled={lockedOff}
                                    onChange={e =>
                                        setEnabled(e.currentTarget.checked)
                                    }
                                    label={t('telemetry.switch')}
                                />
                                {lockedOff && (
                                    <p className='description-small'>
                                        {t('telemetry.disabled_by', {
                                            variable: settings.disabledBy,
                                        })}
                                    </p>
                                )}
                            </>
                        ) : (
                            <Spinner />
                        )}
                    </div>
                </FormCard.Body>
                <FormCard.Footer className='gap-2'>
                    <Button onClick={openReport} size='sm'>
                        {t('telemetry.view_report')}
                    </Button>
                    <Button
                        onClick={save}
                        loading={saving}
                        disabled={
                            !settings ||
                            lockedOff ||
                            enabled === settings.enabled
                        }
                        variant='filled'
                        color='success'
                        size='sm'
                    >
                        {tStrings('save')}
                    </Button>
                </FormCard.Footer>
            </FormCard>
        </>
    )
}

export default TelemetryCard
