import { Params } from 'react-router-dom'

export type WithRequired<T, K extends keyof T> = T & { [P in K]-?: T[P] }

export const randomInt = (low: number, high: number) =>
    Math.floor(Math.random() * (high - low) + low)

export const getInitials = (
    str: string,
    splitBy: string = ' ',
    maxLength: number = 3
) => {
    const words = str.toUpperCase().split(splitBy).slice(0, maxLength)
    const initials = words.map(word => word[0]).join('')
    return initials
}

export const stringToHexColor = (str: string) => {
    let hash = 0
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash)
    }
    let colour = '#'
    for (let i = 0; i < 3; i++) {
        let value = (hash >> (i * 8)) & 0xff
        colour += ('00' + value.toString(16)).substr(-2)
    }
    return colour
}

export interface FormattedBytes {
    size: number
    unit: Sizes
}

export type Sizes =
    | 'B'
    | 'KiB'
    | 'MiB'
    | 'GiB'
    | 'TiB'
    | 'PiB'
    | 'EiB'
    | 'ZiB'
    | 'YiB'

export function formatBytes(
    bytes: number,
    decimals = 2,
    customSize?: Sizes
): FormattedBytes {
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
        unit: sizes[i] as Sizes,
    }
}

export const bytesToString = (bytes: number, decimals = 2): string => {
    const k = 1024

    if (bytes < 1) return '0 Bytes'

    decimals = Math.floor(Math.max(0, decimals))
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    const value = Number((bytes / Math.pow(k, i)).toFixed(decimals))

    return `${value} ${['Bytes', 'KiB', 'MiB', 'GiB', 'TiB'][i]}`
}

export const capitalize = (word: string) => {
    return word.charAt(0).toUpperCase() + word.slice(1)
}

export const convertTimeToSmallest = (seconds: number) => {
    const units = [
        [1, 'seconds'],
        [60, 'minutes'],
        [60 * 60, 'hours'],
        [60 * 60 * 24, 'days'],
    ]
    let bestUnit = units[0]
    for (const unit of units) {
        if (seconds >= unit[0]) {
            bestUnit = unit
        }
    }

    return {
        time: seconds / (bestUnit[0] as number),
        unit: bestUnit[1] as string,
    }
}

export const hexToRgba = (hex: string, alpha = 1): string => {
    // noinspection RegExpSimplifiable
    if (!/#?([a-fA-F0-9]{2}){3}/.test(hex)) {
        return hex
    }

    // noinspection RegExpSimplifiable
    const [r, g, b] = hex.match(/[a-fA-F0-9]{2}/g)!.map(v => parseInt(v, 16))

    return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

export const classNames = (...classes: (string | undefined | null)[]) => {
    return classes.filter(Boolean).join(' ')
}

export const bindUrlParams = (url: string, params: Params<string>) => {
    Object.keys(params).forEach(key => {
        url = url.replace(`:${key}`, params[key]!)
    })

    return url
}

export const countIPsInRange = (
    ipType: 'ipv4' | 'ipv6',
    startIP: string,
    endIP: string
): number => {
    if (startIP === '' || endIP === '') return 0

    try {
        const ipToNumber = (ip: string): bigint => {
            const parts =
                ipType === 'ipv4' ? ip.split('.').map(Number) : ip.split(':')
            let number = BigInt(0)
            for (let i = 0; i < parts.length; i++) {
                if (ipType === 'ipv4') {
                    number = (number << BigInt(8)) + BigInt(parts[i])
                } else {
                    number =
                        // @ts-expect-error
                        (number << BigInt(16)) + BigInt(parseInt(parts[i], 16))
                }
            }
            return number
        }

        const startNumber = ipToNumber(startIP)
        const endNumber = ipToNumber(endIP)

        if (startNumber > endNumber) {
            return 0
        }

        return Number(endNumber - startNumber + BigInt(1))
    } catch {
        return 0
    }
}
