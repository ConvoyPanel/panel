export const randomInt = (low: number, high: number) =>
  Math.floor(Math.random() * (high - low) + low)

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
