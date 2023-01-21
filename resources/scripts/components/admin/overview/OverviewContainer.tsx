import PageContentBlock from '@/components/elements/PageContentBlock'
import { useStoreState } from '@/state'

const OverviewContainer = () => {
    const version = useStoreState(state => state.settings.data!.version)
    return (
        <PageContentBlock title='Overview' showFlashKey='admin:overview'>
            <h1>
                version <strong>{version}</strong>
            </h1>
            <br />
            <p>The admin area is still under construction</p>
        </PageContentBlock>
    )
}

export default OverviewContainer
