import { useAnchors } from '@/features/anchors/api'

import { SelectForm } from '@/components/ui/Forms'

export default function AnchorPicker() {
    const { data } = useAnchors({ perPage: 100, filters: { mode: 'agent' } })
    return (
        <SelectForm
            name='anchorId'
            label='Anchor Agent'
            items={[
                { value: 'none', label: 'Not configured' },
                ...(data?.items ?? []).map(anchor => ({
                    value: String(anchor.id),
                    label: anchor.name,
                })),
            ]}
        />
    )
}
