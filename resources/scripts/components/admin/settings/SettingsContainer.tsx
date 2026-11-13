import { useTranslation } from 'react-i18next'

import PageContentBlock from '@/components/elements/PageContentBlock'

import TelemetryCard from '@/components/admin/settings/TelemetryCard'

const SettingsContainer = () => {
    const { t: tStrings } = useTranslation('strings')

    return (
        <PageContentBlock title={tStrings('setting_other') ?? 'Settings'}>
            <div className='max-w-3xl'>
                <TelemetryCard />
            </div>
        </PageContentBlock>
    )
}

export default SettingsContainer
