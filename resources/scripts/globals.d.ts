declare module '*.png'
declare module '*.svg'
declare module '*.jpeg'
declare module '*.jpg'

declare module '*.module.css' {
    const classes: { [key: string]: string }
    export default classes
}
