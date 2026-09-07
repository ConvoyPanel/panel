import { usePasskeyLogin } from '@/features/auth/passkeys.ts'
import { IconKey } from '@tabler/icons-react'

import { Button } from '@/components/ui/Button'

interface Props {
    redirectTo?: string
}

/*
 * The repair path, not the front door. The login page arms conditional UI on
 * mount, so this exists for the cases that offer cannot reach: a roaming
 * security key with no discoverable credential, a dismissed autofill dropdown
 * (there is no API to reopen one), and browsers where
 * `browserSupportsWebAuthnAutofill()` is false.
 *
 * `ghost`, not `outline`: an outline button would sit at the same weight as
 * "Continue with GitHub", and a passkey is not a parallel choice to a provider.
 * Still a Button rather than a link, because the ceremony has a pending state
 * worth showing and a second press worth blocking.
 */
const LoginWithPasskeyButton = ({ redirectTo }: Props) => {
    const { signIn, isPending } = usePasskeyLogin(redirectTo)

    return (
        <Button
            type={'button'}
            variant={'ghost'}
            onClick={() => signIn()}
            loading={isPending}
            icon={<IconKey className='size-4' />}
        >
            Use a passkey
        </Button>
    )
}

export default LoginWithPasskeyButton
