export const getInitials = (
  str: string,
  splitBy: string = '-',
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

export const adjustHexBrightness = (hexInput: string, percent: number) => {
  let hex = hexInput

  // strip the leading # if it's there
  hex = hex.replace(/^\s*#|\s*$/g, '')

  // convert 3 char codes --> 6, e.g. `E0F` --> `EE00FF`
  if (hex.length === 3) {
    hex = hex.replace(/(.)/g, '$1$1')
  }

  let r = parseInt(hex.substr(0, 2), 16)
  let g = parseInt(hex.substr(2, 2), 16)
  let b = parseInt(hex.substr(4, 2), 16)

  const calculatedPercent = (100 + percent) / 100

  r = Math.round(Math.min(255, Math.max(0, r * calculatedPercent)))
  g = Math.round(Math.min(255, Math.max(0, g * calculatedPercent)))
  b = Math.round(Math.min(255, Math.max(0, b * calculatedPercent)))

  return `#${r.toString(16).toUpperCase()}${g.toString(16).toUpperCase()}${b
    .toString(16)
    .toUpperCase()}`
}

export const convertToAlphaNumericDashes = (str: string) => {
  return str.replace(/\s/g, '-').replace(/[^a-zA-Z0-9-]/g, '')
}
