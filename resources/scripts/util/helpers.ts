export const randomInt = (low: number, high: number) =>
  Math.floor(Math.random() * (high - low) + low)

export const getInitials = (
  str: string,
  splitBy: string = ' ',
  maxLength: number = 3
) => {
  const words = str.toUpperCase().split(splitBy).slice(0, maxLength)
  const initials = words.map((word) => word[0]).join('')
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
  unit: string
}

export type Sizes = 'B' | 'KB' | 'MB' | 'GB' | 'TB' | 'PB' | 'EB' | 'ZB' | 'YB'

export function formatBytes(
  bytes: number,
  decimals = 2,
  customSize?: Sizes
): FormattedBytes {
  if (bytes === 0) return { size: 0, unit: 'B' }

  const k = 1024
  const sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
  const dm = decimals < 0 ? 0 : decimals

  const i = customSize ? sizes.indexOf(customSize) : Math.floor(Math.log(bytes) / Math.log(k))
  const size = parseFloat((bytes / Math.pow(k, i)).toFixed(dm))

  return {
    size,
    unit: sizes[i],
  }
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