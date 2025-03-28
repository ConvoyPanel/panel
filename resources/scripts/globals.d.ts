import { RowData } from '@tanstack/react-table'

declare module '*.png'
declare module '*.svg'
declare module '*.jpeg'
declare module '*.jpg'

declare module '@tanstack/react-router' {
    interface Register {
        router: typeof router
    }

    interface StaticDataRouteOption {
        title?: string
    }
}

declare module '@tanstack/react-table' {
    interface ColumnMeta<TData extends RowData, TValue> {
        skeletonWidth?: string
        align?: 'left' | 'center' | 'right'
    }
}
