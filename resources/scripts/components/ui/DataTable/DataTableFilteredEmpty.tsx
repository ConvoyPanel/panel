import { IconSearch } from '@tabler/icons-react'

import { Button } from '@/components/ui/Button'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'

interface Props {
    onClear: () => void
}

/**
 * Default no-results state for a collection that holds records but has none
 * matching the active search/filters. Distinct from a collection's contextual
 * `emptyState`, which means "nothing has been created yet" and carries the
 * create action instead.
 */
const DataTableFilteredEmpty = ({ onClear }: Props) => (
    <SimpleEmptyState
        icon={IconSearch}
        title={'No matching results'}
        description={'No records match your current search or filters.'}
        action={
            <Button variant={'outline'} onClick={onClear}>
                Clear filters
            </Button>
        }
    />
)

export default DataTableFilteredEmpty
