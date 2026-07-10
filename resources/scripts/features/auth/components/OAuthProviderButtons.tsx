import {
    IconBrandGithub,
    IconBrandGitlab,
    IconBrandGoogle,
    IconLogin2,
    type Icon,
} from '@tabler/icons-react'

import { oauthProviders, oauthRedirectUrl } from '@/features/auth/oauth.ts'

import { Button } from '@/components/ui/Button'

interface Props {
    // The SPA path to return to after a successful login (from the login page's `redirect` search).
    redirectTo?: string
    // Overrides the button copy — e.g. "Connect with" on the account page instead of "Continue with".
    verb?: string
}

const ICONS: Record<string, Icon> = {
    google: IconBrandGoogle,
    github: IconBrandGithub,
    gitlab: IconBrandGitlab,
}

const OAuthProviderButtons = ({ redirectTo, verb = 'Continue with' }: Props) => {
    const providers = oauthProviders()

    if (providers.length === 0) {
        return null
    }

    return (
        <div className='flex w-full flex-col space-y-2'>
            {providers.map((provider) => {
                const ProviderIcon = ICONS[provider.id] ?? IconLogin2

                return (
                    <Button
                        key={provider.id}
                        type='button'
                        variant='outline'
                        className='w-full'
                        onClick={() => {
                            window.location.href = oauthRedirectUrl(
                                provider.id,
                                redirectTo
                            )
                        }}
                    >
                        <ProviderIcon className='mr-2 h-4 w-4' />
                        {verb} {provider.label}
                    </Button>
                )
            })}
        </div>
    )
}

export default OAuthProviderButtons
