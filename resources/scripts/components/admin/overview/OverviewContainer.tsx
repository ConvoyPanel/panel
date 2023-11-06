import { useStoreState } from '@/state'

import PageContentBlock from '@/components/elements/PageContentBlock'

const OverviewContainer = () => {
    const version = useStoreState(state => state.settings.data!.version)
    return (
        <PageContentBlock title='Overview'>
            <h1>
                version <strong>{version}</strong>
            </h1>
            <br />
            <p>The admin area is still under construction</p>
        </PageContentBlock>
    )
}

export default OverviewContainer