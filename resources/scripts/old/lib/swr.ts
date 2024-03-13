import { SWRResponse } from 'swr';


// Makes the data property of the SWRResponse not undefined
export type Optimistic<T extends SWRResponse> = Omit<T, 'data'> & {
    data: NonNullable<T['data']>
}