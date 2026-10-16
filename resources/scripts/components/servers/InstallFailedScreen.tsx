import { ExclamationCircleIcon } from '@heroicons/react/24/outline'
import { useTranslation } from 'react-i18next'

import PageContentBlock from '@/components/elements/PageContentBlock'

import ReinstallServerCard from '@/components/servers/settings/partials/general/ReinstallServerCard'


// A failed install used to be a dead end ("contact your administrator"). The
// server can be reinstalled from here instead: the retry is a reinstall, and
// the API allows it for install_failed servers.
const InstallFailedScreen = () => {
    const { t } = useTranslation('server.settings')

    return (
        <PageContentBlock title={t('retry_install.failed_title')}>
            <div className='mx-auto w-full max-w-xl space-y-6'>
                <div className='text-center'>
                    <ExclamationCircleIcon className='w-16 h-16 border dark:border-stone-600 rounded-md p-3 text-black dark:text-stone-400 mx-auto' />
                    <h2 className='text-stone-900 dark:text-white font-bold text-4xl mt-6'>
                        {t('retry_install.failed_title')}
                    </h2>
                    <p className='description-small mt-3'>
                        {t('retry_install.failed_message')}
                    </p>
                </div>
                <ReinstallServerCard
                    title={t('retry_install.title')}
                    description={t('retry_install.description')}
                    submitLabel={t('retry_install.button')}
                />
            </div>
        </PageContentBlock>
    )
}

export default InstallFailedScreen
