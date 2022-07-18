import { ChangeEvent } from 'react'

function hexToRgba(hex: string, alpha = 1): string {
  // noinspection RegExpSimplifiable
  if (!/#?([a-fA-F0-9]{2}){3}/.test(hex)) {
    return hex
  }

  // noinspection RegExpSimplifiable
  const [r, g, b] = hex.match(/[a-fA-F0-9]{2}/g)!.map((v) => parseInt(v, 16))

  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

function formDataHandler(
  event: ChangeEvent<HTMLInputElement>,
  setData: (name: any, value: any) => void
) {
  setData(
    // @ts-ignore
    event.target.name,
    event.target.type === 'checkbox' ? event.target.checked : event.target.value
  )
}

export { hexToRgba, formDataHandler }
