import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'


export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export type OldSizes =
    | 'B'
    | 'KiB'
    | 'MiB'
    | 'GiB'
    | 'TiB'
    | 'PiB'
    | 'EiB'
    | 'ZiB'
    | 'YiB'

export function oldFormatBytes(
    bytes: number,
    decimals = 2,
    customSize?: OldSizes
) {
    if (bytes === 0) return { size: 0, unit: 'B' }

    const k = 1024
    const sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
    const dm = decimals < 0 ? 0 : decimals

    const i = customSize
        ? sizes.indexOf(customSize)
        : Math.floor(Math.log(bytes) / Math.log(k))
    const size = parseFloat((bytes / Math.pow(k, i)).toFixed(dm))

    return {
        size,
        unit: sizes[i] as OldSizes,
    }
}
