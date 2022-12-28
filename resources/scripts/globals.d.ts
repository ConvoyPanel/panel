import { Alignment } from '@/components/elements/displays/Table'
import '@tanstack/react-table'

declare module '@tanstack/table-core' {
  interface ColumnMeta<TData extends RowData, TValue> {
    overflow?: boolean
    align?: Alignment
  }
}