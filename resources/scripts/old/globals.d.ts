import '@tanstack/react-table'
import { RowData } from '@tanstack/react-table'

import { Alignment } from '@/components/elements/displays/Table'

declare module '@tanstack/table-core' {
    interface ColumnMeta<TData extends RowData, TValue> {
        overflow?: boolean
        align?: Alignment
    }
}
