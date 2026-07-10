declare module '*.png'
declare module '*.svg'
declare module '*.jpeg'
declare module '*.jpg'

interface OAuthProvider {
    id: string
    label: string
}

interface SiteConfiguration {
    version: string
    oauthProviders: OAuthProvider[]
}

interface Window {
    SiteConfiguration?: SiteConfiguration
}

declare module '*.module.css' {
    const classes: { [key: string]: string }
    export default classes
}
