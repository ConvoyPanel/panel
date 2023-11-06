import { ServerContext } from '@/state/server'
import { ArrowTopRightOnSquareIcon } from '@heroicons/react/20/solid'
import { Button } from '@mantine/core'
import { useTranslation } from 'react-i18next'

import Card from '@/components/elements/Card'


const ServerTerminalBlock = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { t } = useTranslation('server.overview')
    const { t: tStrings } = useTranslation('strings')

    const launch = (useXterm: boolean = false, popup: boolean = false) => {
        if (popup) {
            window.open(
                `/servers/${uuid}/terminal?type=${
                    useXterm ? 'xtermjs' : 'novnc'
                }`,
                'Terminal',
                'width=800,height=600'
            )
        } else {
            window.open(
                `/servers/${uuid}/terminal?type=${
                    useXterm ? 'xtermjs' : 'novnc'
                }`,
                '_blank'
            )
        }
    }

    return (
        <Card className='flex flex-col col-span-10 md:col-span-5'>
            <h5 className='h5'>{t('terminal.title')}</h5>
            <p className='description-small mt-1'>
                {t('terminal.description')}
            </p>
            <div className='grid lg:grid-cols-2 mt-6'>
                <div className='flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-accent-200 lg:pr-5 pb-5 lg:py-5'>
                    <div>
                        <h6 className='h6'>noVNC</h6>
                        <p className='description-small mt-1'>
                            {t('terminal.novnc_description')}
                        </p>
                    </div>
                    <Button.Group className='mt-6'>
                        <Button
                            className='grow'
                            variant='outline'
                            onClick={() => launch()}
                        >
                            {tStrings('launch')}
                        </Button>
                        <Button
                            variant='outline'
                            onClick={() => launch(false, true)}
                        >
                            <ArrowTopRightOnSquareIcon className='w-4 h-4' />
                        </Button>
                    </Button.Group>
                </div>
                <div className='flex flex-col justify-between lg:pl-5 pt-5 lg:py-5'>
                    <div>
                        <h6 className='h6'>xTerm.js</h6>
                        <p className='description-small mt-1'>
                            {t('terminal.xtermjs_description')}
                        </p>
                    </div>
                    <Button.Group className='mt-6'>
                        <Button
                            variant='outline'
                            className='grow'
                            onClick={() => launch(true)}
                        >
                            {tStrings('launch')}
                        </Button>
                        <Button
                            variant='outline'
                            onClick={() => launch(true, true)}
                        >
                            <ArrowTopRightOnSquareIcon className='w-4 h-4' />
                        </Button>
                    </Button.Group>
                </div>
            </div>
        </Card>
    )
}

export default ServerTerminalBlock