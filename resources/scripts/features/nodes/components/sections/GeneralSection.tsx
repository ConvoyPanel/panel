import AnchorPicker from '@/features/anchors/components/AnchorPicker.tsx'
import LocationPicker from '@/features/locations/components/LocationPicker.tsx'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { InputForm } from '@/components/ui/Forms'

/**
 * How the node is labelled and grouped in Convoy. Shared by the create page and
 * the node's settings tab, which differ here only in the values they load.
 *
 * The Card carries `@container` so the field grid keys off the card's own width
 * rather than the page's — the two routes wrap it at different widths, and a
 * shared section should not have to know which one it is in.
 */
const GeneralSection = () => (
    <Card className={'@container'}>
        <CardHeader>
            <CardTitle>General</CardTitle>
        </CardHeader>
        <CardContent className={'grid grid-cols-1 gap-3 @2xl:grid-cols-3'}>
            <InputForm name={'displayName'} label={'Display Name'} />
            <LocationPicker />
            <AnchorPicker />
        </CardContent>
    </Card>
)

export default GeneralSection
